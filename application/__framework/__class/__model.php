<?php
/**
 * @author flavienb.com
 */

/**
 * Description of __model
 *
 */
abstract class __model
{
    /**
     * @var \PDO
     */
    protected $connexion;
    protected $database;
    static protected $transactionLevel;

    public final function __construct($database=null)
    {
        if ($database) {
            $this->database = $database;
        }

        if (is_array($this->database)) {
            foreach ($this->database as $index => $name) {
                $this->connexion[$name] = __database::connect($name);

                if (!isset(self::$transactionLevel[$name])) {
                    self::$transactionLevel[$name] = 0;
                }
            }
        } else {
            $this->connexion = __database::connect($this->database);

            if (!isset(self::$transactionLevel[$this->database])) {
                self::$transactionLevel[$this->database] = 0;
            }
        }

        $this->init();
    }

    /**
     * Méthode appelée après l'instanciation du controller, à surcharger
     */
    abstract protected function init();

    public final function beginTransaction($database = '')
    {
        if ($database) {
            if (self::$transactionLevel[$database] == 0) {
                $this->connexion[$database]->beginTransaction();
            }
            self::$transactionLevel[$database]++;

        } else {
            if (self::$transactionLevel[$this->database] == 0) {
                $this->connexion->beginTransaction();
            }
            self::$transactionLevel[$this->database]++;
        }
    }

    public final function resetTransaction($database = '')
    {
        if ($database) {
            if ($this->connexion->inTransaction()) {
                $this->connexion->rollBack();
            }
            self::$transactionLevel[$database] = 0;

        } else {
            if ($this->connexion->inTransaction()) {
                $this->connexion->rollBack();
            }
            self::$transactionLevel[$this->database] = 0;
        }
    }

    public final function rollBack($database = '')
    {
        if ($database) {
            if (self::$transactionLevel[$database] == 1) {
                $this->connexion->rollBack();
            }
            self::$transactionLevel[$database]--;

        } else {
            if (self::$transactionLevel[$this->database] == 1) {
                $this->connexion->rollBack();
            }
            self::$transactionLevel[$this->database]--;
        }
    }

    public final function commit($database = '')
    {
        if ($database) {
            if (self::$transactionLevel[$database] == 1) {
                $this->connexion[$database]->commit();
            }
            self::$transactionLevel[$database]--;
        } else {
            if (self::$transactionLevel[$this->database] == 1) {
                $this->connexion->commit();
            }
            self::$transactionLevel[$this->database]--;
        }
    }

    public final function setLock($lockName)
    {
        $result = $this->connexion->query("SELECT GET_LOCK('$lockName',1) as l");
        $lock = $result->fetch(PDO::FETCH_ASSOC);

        return $lock['l'];
    }

    public final function releaseLock($lockName)
    {
        $result = $this->connexion->query("SELECT RELEASE_LOCK('$lockName') as l");
        $lock = $result->fetch(PDO::FETCH_ASSOC);

        return $lock['l'];
    }

    public final function __select($table, $whereData, $fields = null, $limit=null)
    {
        $whereStr = '';

        foreach ((array)$whereData as $key => $where) {
            if ($where === null) {
                $value = 'IS NULL';
                unset($whereData[$key]);
            }
            elseif(is_numeric($where)){
                $value = "= $where";
                unset($whereData[$key]);
            }
            else{
                $value = "=:$key";
            }

            $whereStr .= "$key $value AND ";
        }
        $whereStr = substr($whereStr, 0, -4);

        $fields = $fields ? implode(',', $fields) : '*';

        $reqLimit = '';
        if ($limit !== null) {
            $reqLimit = "LIMIT $limit";
        }

        $prep = $this->connexion->prepare("SELECT $fields FROM $table WHERE $whereStr $reqLimit");

        $prep->execute($whereData);

        return $limit === 1?$prep->fetch(PDO::FETCH_ASSOC):$prep->fetchAll(PDO::FETCH_ASSOC);
    }

    public final function __insert($table, $data, $ignore = false)
    {
        $fields = implode(',', array_keys($data));
        $fieldsValues = ':' . implode(',:', array_keys($data));

        $ignoreStr = $ignore ? "IGNORE" : "";

        $prep = $this->connexion->prepare("INSERT $ignoreStr INTO $table ($fields) VALUES ($fieldsValues)");
        $prep->execute($data);

        return $this->connexion->lastInsertId();
    }

    public final function __update($table, $data, $whereData)
    {
        $updateStr = '';
        $whereStr = '';

        foreach ((array)$data as $key => $value) {
            $value = ":$key";
            $updateStr .= "$key=$value,";
        }

        foreach ((array)$whereData as $key => $where) {
            if ($where === null) {
                $value = 'IS NULL';
                unset($whereData[$key]);
            }
            elseif(is_numeric($where)){
                $value = "= $where";
                unset($whereData[$key]);
            }
            else{
                $value = "=:$key";
            }

            $whereStr .= "$key $value AND ";
        }
        $updateStr = substr($updateStr, 0, -1);
        $whereStr = substr($whereStr, 0, -4);

        $prep = $this->connexion->prepare("UPDATE $table SET $updateStr WHERE $whereStr");

        $prep->execute(array_merge($data, $whereData));

        return $prep->rowCount();
    }

    public final function __delete($table, $whereData)
    {
        $whereStr = '';
        foreach ((array)$whereData as $key => $where) {
            $value = ($where !== null) ? "=:$key" : 'IS NULL';
            $whereStr .= "$key $value AND ";
        }
        $whereStr = substr($whereStr, 0, -4);

        $prep = $this->connexion->prepare("DELETE FROM $table WHERE $whereStr");
        $prep->execute($whereData);

        return $prep->rowCount();
    }

    public final function prepare($sql,$database = '') {
        if ($database) {
            return $this->connexion[$database]->prepare($sql);
        } else {
            return $this->connexion->prepare($sql);
        }
    }

}

?>
