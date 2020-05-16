<?php
/**
 * @author flavienb.com
 */

/**
 * Class __store
 * @method static get($key, $namespace = "")
 * @method static has($key, $namespace = "")
 * @method static set($key, $data, $namespace = "")
 * @method static setAtomic($key, $data, $namespace = "")
 * @method static exists($key, $namespace = "")
 * @method static clear($namespace = "")
 * @method static destroy()
 * @method static remove($key, $namespace = "")
 * @method static setNamespace($namespace)
 * @method static id()
 */
final class __store
{

    static private $initialized = false;
    static private $data = array();
    static private $namespace = "";
    static private $isModified = false;
    static private $lifetime = null;

    private static function _start()
    {
        //ini_set('session.gc_probability', 100);
        //ini_set('session.gc_divisor', 1);

        if (__config::exists('SESSION_NAME')) {
            session_name(__config::get('SESSION_NAME'));
        }

        if (__config::has('SESSION_PATH')) {
            session_save_path(__config::get('SESSION_PATH'));
        }

        if (__config::exists('SESSION_TIMEOUT')) {
            self::$lifetime = __config::get('SESSION_TIMEOUT');
            ini_set('session.cookie_lifetime', self::$lifetime);
            ini_set('session.gc_maxlifetime', self::$lifetime);
        }

        ini_set('session.cookie_httponly', 1);

        if (__config::exists('SESSION_DOMAIN')) {
            session_set_cookie_params(self::$lifetime,'/',__config::get('SESSION_DOMAIN'),false,true);
        }


        self::open();
    }

    private static function open($lock=false) {
        session_start();
        self::$initialized = true;

        if (self::$lifetime && isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > self::$lifetime)) {
            session_destroy();
        }
        else {
            if (isset($_SESSION['__'])) {
                self::$data = unserialize($_SESSION['__']);
            }
            if (!isset(self::$data['__default'])) {
                self::$data['__default'] = array();
            }
        }

        $_SESSION['LAST_ACTIVITY'] = time();

        if (!$lock) {
            session_write_close();
            unset($_SESSION);
        }
    }

    public static function lock() {
        self::open(true);
    }

    public static function save()
    {
        if (self::$initialized && !headers_sent() && self::$isModified) {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }
            $_SESSION['__'] = serialize(self::$data);
        }
        session_write_close();
    }

    private static function _get($key, $namespace = "")
    {
        if ($namespace && isset(self::$data[$namespace]) && isset(self::$data[$namespace][$key])) {
            return self::$data[$namespace][$key];
        }
        elseif (self::$namespace && isset(self::$data[$namespace][$key])) {
            return self::$data[self::$namespace][$key];
        }
        elseif (isset(self::$data['__default'][$key])) {
            return self::$data['__default'][$key];
        }
    }

    private static function _set($key, $data, $namespace = "")
    {
        if ($namespace) {
            self::$data[$namespace][$key] = $data;
        }
        elseif (self::$namespace) {
            self::$data[self::$namespace][$key] = $data;
        }
        else {
            self::$data['__default'][$key] = $data;
        }

        self::$isModified = true;
    }

    private static function _destroy()
    {
        self::$data = array();
        self::$isModified = true;
    }

    private static function _clear($namespace = "")
    {
        if ($namespace) {
            unset(self::$data[$namespace]);
        }
        elseif (self::$namespace) {
            unset(self::$data[self::$namespace]);
        }
        else {
            unset(self::$data['__default']);
        }

        self::$isModified = true;
    }

    private static function _remove($key, $namespace = "")
    {
        if ($namespace) {
            unset(self::$data[$namespace][$key]);
        }
        elseif (self::$namespace) {
            unset(self::$data[self::$namespace][$key]);
        }
        else {
            unset(self::$data['__default'][$key]);
        }

        self::$isModified = true;
    }

    private static function _exists($key, $namespace = "")
    {
        if ($namespace) {
            return isset(self::$data[$namespace][$key]);
        }
        elseif (self::$namespace) {
            return isset(self::$data[self::$namespace][$key]);
        }
        else {
            return isset(self::$data['__default'][$key]);
        }
    }

    private static function _has($key, $namespace = "")
    {
        if ($namespace) {
            return !empty(self::$data[$namespace][$key]);
        }
        elseif (self::$namespace) {
            return !empty(self::$data[self::$namespace][$key]);
        }
        else {
            return !empty(self::$data['__default'][$key]);
        }
    }

    private static function _setNamespace($namespace)
    {
        self::$namespace = $namespace;
    }

    private static function _id()
    {
        return session_id();
    }

    public static function __callStatic($method, $arguments)
    {
        if (method_exists('__store', '_' . $method)) {
            if (!self::$initialized) {
                self::_start();
            }
            return call_user_func_array(array('__store', '_' . $method), $arguments);
        } else {
            throw new Exception("Call to undefined method");
        }
    }

}

class_alias('__store', '__sess');

