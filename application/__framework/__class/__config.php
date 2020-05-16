<?php
/**
 * @author flavienb.com
 */

final class __config
{
    /**
     * @var __registry
     */
    private static $registry = null;

    public static function init($config)
    {
        self::$registry = __registry::setRegistry('__config', $config);
    }

    public static function get($key)
    {
        return self::$registry->get($key);
    }

    public static function set($key, $value=null)
    {
        self::$registry->set($key, $value);
    }

    public static function exists($key) {
        return self::$registry->exists($key);
    }

    public static function has($key) {
        return self::$registry->has($key);
    }
}
