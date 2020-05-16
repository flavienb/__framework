<?php
/**
 * @author flavienb.com
 */

final class __request
{

    static private $data = array();
    static private $module = null;
    static private $controller = null;
    static private $action = null;
    static private $argv = null;
    static private $path = '';
    static private $pathArray = array();
    static private $domain = '';
    static private $uploadHandler = null;

    public static function init($argv = null)
    {
        self::$module = __config::get('DEFAULT_MODULE');
        self::$controller = __config::get('DEFAULT_CONTROLLER');
        self::$action = __config::get('DEFAULT_ACTION');

        if (!$argv) {
            self::$data = array_merge($_GET, $_POST);
            unset($_GET, $_POST, $_REQUEST);
        } else {
            self::$argv = $argv;
            if (strpos($argv[1], '?') !== false) {
                $arguments_s = substr($argv[1], strpos($argv[1], '?') + 1);
                $arguments_t = explode('&', $arguments_s);

                foreach ((array)$arguments_t as $arg) {
                    if ($values = explode('=', $arg)) {
                        self::$data[$values[0]] = urldecode($values[1]);
                    }
                }
            }
        }

        if (!empty($_FILES)) {
            require(FRAMEWORK_PATH . '__lib/upload.handler.php');
            self::$uploadHandler = new UploadHandler();
        }

        self::processRequestURI();
    }

    public static function formatData(&$item)
    {
        return $item = htmlspecialchars(urldecode($item), ENT_NOQUOTES, 'UTF-8');
    }

    public static function raw($key)
    {
        if (isset(self::$data[$key])) {
            return self::$data[$key];
        }
    }

    public static function rawAll()
    {
        return self::$data;
    }

    public static function get($key, $json = false)
    {
        if (isset(self::$data[$key])) {
            $return = self::$data[$key];
            if (is_array($return)) {
                array_walk_recursive($return, 'self::formatData');
            } else {
                $return = self::formatData($return);
            }
            if ($json) {
                $return = json_decode(htmlspecialchars_decode($return, ENT_NOQUOTES), true);
            }
            return $return;
        }
    }

    public static function all()
    {
        $return = array();
        foreach ((array)self::$data as $key => $value) {
            $return[$key] = self::get($key);
        }
        return $return;
    }

    /**
     * Tester l'existence des paramètres
     * @param $required
     * @param array $optionals
     * @return array|null
     */
    public static function checkParams($required, $optionals = array())
    {
        return self::_params($required, $optionals, false);
    }

    /**
     * Tester l'existence et la valeur des paramètres
     * @param $required
     * @param array $optionals
     * @return array|null
     */
    public static function hasParams($required, $optionals = array())
    {
        return self::_params($required, $optionals, true);
    }

    /**
     * Obtenir les paramètres de l'url formattés (&param=value&param2=value2 ...)
     */
    public static function getURI()
    {
        return http_build_query(self::$data);
    }

    public static function exists($key)
    {
        return isset(self::$data[$key]);
    }

    public static function has($key)
    {
        return !empty(self::$data[$key]);
    }

    public static function set($key, $value = null)
    {
        if (is_array($key)) {
            self::$data = array_merge(self::$data, $key);
        } else {
            self::$data[$key] = $value;
        }
    }

    public static function remove($key)
    {
        unset(self::$data[$key]);
    }

    public static function removeAll()
    {
        foreach ((array)self::$data as $key => $data) {
            unset(self::$data[$key]);
        }
    }

    public static function getString()
    {
        return $_SERVER['QUERY_STRING'];
    }

    private static function _params($required, $optionals = array(), $testEmpty = false)
    {
        $params = array();

        foreach ((array)$optionals as $param) {
            $params[$param] = self::get($param);
        }

        foreach ((array)$required as $param) {
            if (self::exists($param) && (!$testEmpty || self::has($param))) {
                $params[$param] = self::get($param);
            } else {
                $params = null;
                break;
            }
        }

        return $params;
    }

    private static function processRequestURI()
    {

        //Si l'application est exécutée en CLI, on récupère les paramètres
        if (isset(self::$argv)) {
            array_shift(self::$argv);
            $_SERVER['REQUEST_URI'] = self::$argv[0];
        } else {
            self::$argv = array();
        }

        self::$domain = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '';

        //Récupération des paramètres de l'url
        //On retire tous les paramètres get après le ? s'il existe
        $pos = strpos($_SERVER['REQUEST_URI'], '?');

        self::$pathArray = explode("/", ($pos ? substr($_SERVER['REQUEST_URI'], 0, $pos) : $_SERVER['REQUEST_URI']));
        self::$pathArray = array_values(array_diff(self::$pathArray, array(null)));

        //On récupère les trois derniers éléments du path
        $path = array_slice(array_values(array_filter(self::$pathArray, function ($value) {
            return !in_array($value, (array)__config::get('LOCALE_URL_MAP'));
        })), -3, 3);
        self::$path = '/' . implode('/', $path);
        $nbParams = count($path);

        //On charge le mapping des domaines enregistrés
        $domains = require(APPLICATION_PATH . 'config/domains.php');

        if (isset($domains[__request::$domain])) {
            //Si on autorise une liste de modules par domaine
            if (is_array($domains[__request::$domain])) {
                //On parcourt la liste des modules disponibles
                $foundModule = null;
                foreach ((array)$domains[__request::$domain] as $potentialModule) {
                    if (is_array($potentialModule)) {
                        //Si l'élément est un array, on choisit le module dont la clé correspond au premier élément du path
                        if ($nbParams > 0 && isset($potentialModule[$path[0]])) {
                            $foundModule = $potentialModule[$path[0]];
                            unset($path[0]);
                            $path = array_values($path);
                            $nbParams--;
                            break;
                        }
                    } else {
                        if ($nbParams > 2 && $path[0] == $potentialModule) {
                            $foundModule = $potentialModule;
                        }
                    }
                }

                if (!$foundModule) {
                    //On dispatch par défaut vers le premier domaine de cette liste
                    self::$module = $domains[__request::$domain][0];
                } else {
                    self::$module = $foundModule;
                }

            } else {
                self::$module = $domains[__request::$domain];
            }
        }


        if ($nbParams > 2) {
            self::$action = $path[2];
            self::$controller = $path[1];
            if (!empty(self::$argv)) {
                self::$module = $path[0];
            }
        } else {
            if ($nbParams > 1) {
                self::$action = $path[1];
                self::$controller = $path[0];
            } elseif ($nbParams) {
                self::$action = $path[0];
            }
        }

    }

    /**
     * @return string
     */
    public static function getAction()
    {
        return self::$action;
    }

    /**
     * @return string
     */
    public static function getController()
    {
        return self::$controller;
    }

    /**
     * @return string
     */
    public static function getDomain()
    {
        return self::$domain;
    }

    /**
     * @return string
     */
    public static function getModule()
    {
        return self::$module;
    }

    /**
     * @param string $action
     */
    public static function setAction($action)
    {
        self::$action = $action;
    }

    /**
     * @param string $controller
     */
    public static function setController($controller)
    {
        self::$controller = $controller;
    }

    /**
     * @param string $module
     */
    public static function setModule($module)
    {
        self::$module = $module;
    }

    /**
     * @return array
     */
    public static function getPathArray()
    {
        return self::$pathArray;
    }

    /**
     * @return string
     */
    public static function getPath()
    {
        return self::$path;
    }

    public static function parseJsonInput() {
        $request = json_decode(file_get_contents("php://input"), true);
        if ($request) {
            self::set($request);
        }
    }

    public static function getUploadHandler() {
        return self::$uploadHandler;
    }

}

class_alias('__request', '__req');
