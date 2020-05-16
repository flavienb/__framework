<?php
/**
 * @author flavienb.com
 */

final class __registry
{

    private $data = null;
    private $id = 0;
    private $parent = null;
    private static $instances = array();


    public function __construct($name = '', $data = array())
    {
        if (!empty($name)) {
            self::$instances[$name] = $this;
            $this->id = $name;
        } else {
            self::$instances[] = $this;
            $this->id = count(self::$instances) - 1;
        }
        $this->data = $data;
    }

    public function get($key)
    {
        if (isset($this->data[$key])) {
            return $this->data[$key];
        }
    }

    public function getAll()
    {
        return $this->data;
    }

    public function set($key, $value = null)
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data,$key);
        }
        else {
            $this->data[$key] = $value;
        }
    }

    public function exists($key)
    {
        return isset($this->data[$key]);
    }

    public function has($key)
    {
        return !empty($this->data[$key]);
    }

    public function clear()
    {
        $this->data = array();
    }

    public static function setRegistry($name = '', $data = array())
    {
        return new __registry($name, $data);
    }

    public static function getRegistry($name)
    {
        if (isset(self::$instances[$name])) {
            return self::$instances[$name];
        }
    }

    public static function getAllRegistries()
    {
        return self::$instances;
    }

    public function __destruct()
    {
        unset(self::$instances[$this->id]);
    }

    public function __call($method, $arguments)
    {
        if (isset($this->parent)) {
            if (method_exists($this->parent, $method)) {
                call_user_func_array(array($this->parent, $method), $arguments);
            } elseif (method_exists(get_class($this->parent), $method)) {
                call_user_func_array(array(get_class($this->parent), $method), $arguments);
            } else {
                throw new Exception("Call to undefined method");
            }
        }
    }

}
