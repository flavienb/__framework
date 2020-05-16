<?php
/**
 * @author flavienb.com
 */

final class __action
{

    static private $datasource;
    static private $database;
    static private $dbLockName = '__framework.actions';
    static private $salt = 'cRoNJob$Alt';
    static private $purgeTime = 72; //en heures
    static private $isRequestGenuine = false;
    private static $modulesOverride = [];

    static public function init($argv = null)
    {
        //Si l'application est exécutée en CLI, on lance seulement l'exécution des actions
        if ($argv && strpos($argv[1], 'action/execute') !== false) {
            self::$database = $argv[2]; //on récupère le nom de la BD pour l'exécution des Actions
            self::$datasource = __database::connect(self::$database);

            if (strpos($argv[1], 'action/execute/purge') !== false) {
                self::purge();
            } else {
                self::execute();
            }

            exit;
        }
    }

    public static function override($search, $replace)
    {
        self::$modulesOverride[$search] = $replace;
    }

    static public function register($action = '', $priority = 0, $force_insert = false, $idgroupe = 0)
    {
        self::$datasource = __database::connect(__config::get('ACTION_DATABASE'));
        if ($action) {
            $components = explode('/', $action);
            $module = $components[0];
            if (isset(self::$modulesOverride[$module])) {
                $components[0] = self::$modulesOverride[$module];
                $action = implode('/', $components);
            }

            try { //
                if (!($inTransaction = self::$datasource->inTransaction()))
                    self::$datasource->beginTransaction();

                self::setLock(self::$dbLockName);

                self::insertAction(Array(
                    'iduser' => null,
                    'action' => $action,
                    'priority' => $priority,
                    'date_insert' => time(),
                    'idgroupe' => $idgroupe
                ), $force_insert);


                self::releaseLock(self::$dbLockName);
                if (!$inTransaction)
                    self::$datasource->commit();
            } catch (Exception $ex) {
                self::$datasource->rollBack();
                throw $ex;
            }
        }
    }

    static public function executeOne($idgroupe = 0, $waitErrorIds = array())
    {

        self::$datasource = __database::connect(__config::get('ACTION_DATABASE'));
        $running = false;
        if (self::setLock(self::$dbLockName)) {
            self::$datasource->beginTransaction();
            $action = self::getActionsToExecute($waitErrorIds, $idgroupe);
            if ($idgroupe != 1)
                $running = self::isActionRunning($idgroupe);
            $idaction = $action['idaction'];

            if ($action && (!$running || $action['priority'] >= 99)) {
                self::setActionExecute($action['idaction']);
                $running = false;
            }

            self::$datasource->commit();
            self::releaseLock(self::$dbLockName);

            if ($action && !$running) {

                if (substr($action['action'], 0, 7) == 'http://') {
                    $result = file_get_contents($action['action']);
                } else {
                    $action['action'] .= (((strpos($action['action'], '?') === false) ? '?' : '&') . 'idaction=' . $action['idaction']);

                    //On retire le paramètre hash éventuellement déjà présent
                    $action['action'] = preg_replace('/&hash=([^&]+)/', '', $action['action']);

                    $hash = self::hash($action['action']);

                    $path = escapeshellarg($action['action'] . '&hash=' . $hash);

                    $result = shell_exec("nohup /usr/bin/php -q " . PUBLIC_PATH . "index.php $path"); //> /dev/null
                }

                $resultJ = json_decode($result, true);

                //S'il y a eu une erreur lors de l'exécution, on la log
                if ($result && (!$resultJ || empty($resultJ['status']))) {
                    self::setActionError($idaction, (isset($resultJ['message']) ? $resultJ['message'] : $result));
                    $waitErrorIds[] = $idaction;
                    //TODO envoyer un mail d'avertissement
                } //Si l'action n'a pas pu être executée (pause...), on arrête la processus
                else {
                    $result = null;
                    self::setActionEnd($idaction);
                }
                __database::disconnect(self::$database);

                return array(
                    'error' => $result ? true : false,
                    'data' => $resultJ,
                    'action' => $action['action']
                );
            }
        }

        return false;
    }

    static private function execute($waitErrorIds = Array())
    {
        try {
            $maxActions = 22;
            $maxActionPerGroup = 12;

            for ($i = 0; $i < $maxActions;) {
                $groups = self::getActionGroups();
                if ($groups) {
                    foreach ((array)$groups as $group) {
                        $i++;
                        self::$datasource = __database::connect(self::$database);
                        $idgroupe = $group['idgroupe'];

                        $action = true;
                        for ($a = 0; $a < $maxActionPerGroup && $action; $a++) {
                            if (self::setLock(self::$dbLockName)) {

                                $action = self::getActionsToExecute($waitErrorIds, $idgroupe);

                                $running = self::isActionRunning($idgroupe);
                                $idaction = $action['idaction'];

                                if ($action && (!$running || $action['priority'] >= 99)) {
                                    self::setActionExecute($action['idaction']);
                                    $running = false;
                                }

                                self::releaseLock(self::$dbLockName);

                                if ($action && !$running) {
                                    __database::disconnect(self::$database);
                                    if (substr($action['action'], 0, 7) == 'http://') {
                                        $result = file_get_contents($action['action']);
                                    } else {
                                        $action['action'] .= (((strpos($action['action'], '?') === false) ? '?' : '&') . 'idaction=' . $action['idaction']);

                                        //On retire le paramètre hash éventuellement déjà présent
                                        $action['action'] = preg_replace('/&hash=([^&]+)/', '', $action['action']);

                                        $hash = self::hash($action['action']);

                                        $path = escapeshellarg($action['action'] . '&hash=' . $hash);

                                        $result = shell_exec("/usr/bin/php -q " . PUBLIC_PATH . "index.php $path"); //> /dev/null
                                    }

                                    $resultJ = json_decode($result, true);

                                    self::$datasource = __database::connect(self::$database);
                                    //S'il y a eu une erreur lors de l'exécution, on la log
                                    if ($result && (!$resultJ || $resultJ['status'] != 1)) {
                                        self::setActionError($idaction, ($resultJ['message'] ? $resultJ['message'] : $result));
                                        $waitErrorIds[] = $idaction;
                                    } //Si l'action n'a pas pu être executée (pause...), on arrête la processus
                                    elseif ($resultJ['wait'] == 1) {
                                        self::setActionExecute($action['idaction'], $reset = true);
                                        return false;
                                    } else {
                                        self::setActionEnd($idaction);
                                    }
                                }
                            } else {
                                break;
                            }
                        }
                    }
                } else {
                    break;
                }
            }

        } catch (Exception $ex) {
            //echo $ex->getMessage();
            if (self::$datasource->inTransaction())
                self::$datasource->rollBack();
            throw $ex;
        }
        return false;
    }


    /**
     * Récupérer une liste d'actions
     */
    static public function getActionGroups()
    {
        $prep = self::$datasource->prepare("SELECT idgroupe FROM actions
            WHERE (date_start IS NULL OR (error_msg IS NOT NULL AND executed_time < 1))
            AND (due_date IS NULL OR UNIX_TIMESTAMP() >= due_date)
            GROUP BY idgroupe
            ORDER BY priority DESC");
        $prep->execute();

        return $prep->fetchAll(PDO::FETCH_ASSOC);

    }

    static private function purge()
    {
        $prep = self::$datasource->prepare("DELETE FROM actions
            WHERE date_insert < :date_purge"); //");
        $prep->execute(Array(
            'date_purge' => time() - (self::$purgeTime * 60 * 60)
        ));

        $prep = self::$datasource->prepare("DELETE FROM actions_errors
            WHERE date_error < :date_purge"); //");
        $prep->execute(Array(
            'date_purge' => time() - (self::$purgeTime * 60 * 60)
        ));

    }

    static public function hash($str)
    {
        return md5(self::$salt . $str . self::$salt);
    }

    static public function isRequestGenuine()
    {
        $request = substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], '&hash='));
        return self::$isRequestGenuine || ($request && __request::get('hash') && __request::get('hash') == self::hash($request));
    }

    static public function setRequestGenuine($value = true)
    {
        self::$isRequestGenuine = $value;
    }

    static public function setLock($lockName)
    {
        $result = self::$datasource->query("SELECT GET_LOCK('$lockName',1) as l");
        $lock = $result->fetch(PDO::FETCH_ASSOC);
        return $lock['l'];
    }

    static public function releaseLock($lockName)
    {
        $result = self::$datasource->query("SELECT RELEASE_LOCK('$lockName') as l");
        $lock = $result->fetch(PDO::FETCH_ASSOC);
        return $lock['l'];
    }

    /*
     * Méthodes d'accès à la base de données
     */
    static private function insertAction($data, $force_insert = false)
    {
        $doInsert = true;
        if (!$force_insert) {
            $prep = self::$datasource->prepare("SELECT idaction FROM actions WHERE date_end IS NULL AND action LIKE :action");
            $prep->execute(Array(
                'action' => $data['action']
            ));
            if ($prep->fetch())
                $doInsert = false;
        }

        if ($doInsert) {
            $prep = self::$datasource->prepare("INSERT INTO actions (iduser,action,priority,date_insert,idgroupe)
            VALUES (:iduser,:action,:priority,:date_insert,:idgroupe)");

            $prep->execute(Array(
                'iduser' => null,
                'action' => $data['action'],
                'priority' => $data['priority'],
                'date_insert' => $data['date_insert'],
                'idgroupe' => $data['idgroupe']
            ));

            return self::$datasource->lastInsertId();
        }
    }

    /**
     * Récupérer les actions en cours d'excécution (moins de 5 minutes)
     * @param $delay
     */
    static private function isActionRunning($idgroupe = 0)
    {
        $prep = self::$datasource->prepare("SELECT * FROM actions
            WHERE date_start IS NOT NULL AND date_end IS NULL AND error_msg IS NULL AND idgroupe = $idgroupe"); //");
        $prep->execute();

        //$processes = explode(' ',shell_exec('pgrep -f -d " " action/execute'));

        foreach ((array)$prep->fetchAll(PDO::FETCH_ASSOC) as $action) {
            //Si le processus est terminé
            if ($action['pid']) {
                if (!posix_kill($action['pid'], 0)) {
                    //On update l'action pour mettre une date end
                    self::setActionError($action['idaction'], 'Error: process crash ' . time());
                } else
                    return true;
            }
        }
        return false;
    }

    /**
     * Récupérer les actions qui ne sont pas terminées après un certain temps (5 minutes) ou qui ont généré une erreur
     * @param $delay
     */
    static private function getAbortedAction($delay = 300, $max_execution = 3)
    {
        $prep = self::$datasource->prepare("SELECT * FROM actions
            WHERE date_end IS NULL AND date_update < :date_update AND executed_time < :max_execution");
        $prep->execute(Array(
            'date_update' => time() - $delay,
            'max_execution' => $max_execution
        ));

        return $prep->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer la prochaine action à exécuter
     */
    static private function getActionsToExecute($waitErrorIds = null, $idgroupe = 0)
    {
        $waitErrorTest = '';
        if ($waitErrorIds && $wait_ids = implode("','", $waitErrorIds)) {
            $waitErrorTest = $wait_ids ? "AND idaction NOT IN ('$wait_ids')" : '';
        }

        $sql = "SELECT * FROM actions
            WHERE (date_start IS NULL OR (error_msg IS NOT NULL AND executed_time < 1))
            AND (due_date IS NULL OR UNIX_TIMESTAMP() >= due_date)
            AND idgroupe = $idgroupe $waitErrorTest
            ORDER BY priority DESC, idaction ASC LIMIT 1";


        $prep = self::$datasource->prepare($sql);
        $prep->execute();

        $action = $prep->fetch(PDO::FETCH_ASSOC);

        //echo '--------'.$action['idaction'].'-------';

        if ($action['wait_action_parent']) {
            $idaction_parent = "'" . str_replace(',', "','", $action['idaction_parent']) . "'";
            $idaction_parent_t = explode(',', $action['idaction_parent']);
            $find_in_set = '';

            //On va chercher les actions qui les même idaction_parent que l'action en cours
            foreach ((array)$idaction_parent_t as $idaction_find) {
                if ($idaction_find)
                    $find_in_set .= " OR FIND_IN_SET($idaction_find,idaction_parent)";
            }

            $sql = "SELECT * FROM actions
            WHERE (idaction IN ($idaction_parent) $find_in_set) AND (date_end IS NULL OR error_msg IS NOT NULL)
            AND idaction != :idaction AND idaction < :idaction AND idgroupe = :idgroupe";
            $prep = self::$datasource->prepare($sql);
            $prep->execute(Array(
                'idaction' => $action['idaction'],
                'idgroupe' => $idgroupe
            ));

            //Si j'ai un résultat, je vais chercher l'action suivante à exécuter
            if ($test = $prep->fetch(PDO::FETCH_ASSOC)) {
                $waitErrorIds[] = $action['idaction'];
                $action = self::getActionsToExecute($waitErrorIds, $idgroupe);
            }
        }


        return $action;
    }

    /**
     * Mettre à jour la date_start et date_update
     */
    static private function setActionExecute($idaction, $reset = false)
    {
        $prep = self::$datasource->prepare("UPDATE actions
            SET date_start = :date_start, date_update = :date_update, date_end = NULL, executed_time = " . ((!$reset) ? "executed_time+1" : 0) . ", pid = :pid
            WHERE idaction = :idaction");

        $prep->execute(Array(
            'idaction' => $idaction,
            'date_start' => (!$reset) ? time() : null,
            'date_update' => time(),
            'pid' => posix_getpid()
        ));
    }

    /**
     * Mettre à jour le message d'erreur de l'action
     */
    static private function setActionError($idaction, $errorMessage)
    {
        $prep = self::$datasource->prepare("UPDATE actions
            SET error_msg = :error_msg, date_end = :date_end
            WHERE idaction = :idaction");

        $prep->execute(Array(
            'idaction' => $idaction,
            'error_msg' => $errorMessage,
            'date_end' => time()
        ));


        $prep = self::$datasource->prepare("INSERT INTO actions_errors (idaction,date_error,error)
            VALUES (:idaction,:date_error,:error)");

        $prep->execute(Array(
            'idaction' => $idaction,
            'error' => $errorMessage,
            'date_error' => time()
        ));
    }

    /**
     * Mettre à jour le message d'erreur de l'action
     */
    static private function setActionEnd($idaction)
    {
        $prep = self::$datasource->prepare("UPDATE actions
            SET date_end = :date_end, error_msg = :error_msg
            WHERE idaction = :idaction");

        $prep->execute(Array(
            'idaction' => $idaction,
            'date_end' => time(),
            'error_msg' => null,
        ));
    }

    /**
     * Récupérer une liste d'actions
     */
    static public function getActions($idaction_t)
    {
        if ($idactions = implode(',', $idaction_t)) {

            $prep = self::$datasource->prepare("SELECT * FROM actions
        WHERE idaction IN ($idactions)");
            $prep->execute();

            return $prep->fetchAll(PDO::FETCH_ASSOC);

        }
    }

}

?>
