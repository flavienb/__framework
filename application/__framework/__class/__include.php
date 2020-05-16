<?php
/**
 * @author flavienb.com
 */

final class __include
{

    private static $modulesOverride = [];
    private static $modulesOverrided = [];

    public static function override($search, $replace) {
        self::$modulesOverride[$search] = $replace;
        self::$modulesOverrided[$replace] = $search;
    }

    public static function absolute_class($bepath) {
        $components = explode('/', $bepath);
        $module = $components[0];
        if (isset(self::$modulesOverrided[$module])) {
            $components[0] = self::$modulesOverrided[$module];
            $bepath = implode('/', $components);
        }
        return str_replace('/','_',$bepath);
    }

    public static function path($bepath)
    {
        if (strpos($bepath,'..') === false) {
            $components = explode('/', $bepath);
            $components = array_diff($components, array(null));


            $isLib = strpos($bepath,'lib/') === 0;
            $rootFolder = $isLib ? '' : 'modules/';

            if (!$isLib) {
                $module = $components[0];
                if (isset(self::$modulesOverride[$module])) {
                    $components[0] = self::$modulesOverride[$module];
                    $bepath = implode('/', $components);
                }
            }

            $file = end($components);
            $ext = '.php';

            if ($file && preg_match('/\.([a-zA-Z0-9]+)$/',$file,$matches)) {
                $ext = $matches[1];
                $bepath = substr($bepath, 0, strpos($bepath,$ext));
            } elseif (isset($components[1]) && ($components[1] == "layout" || $components[1] == "view")) {
                $ext = '.phtml';
            }

            return APPLICATION_PATH . $rootFolder . $bepath . $ext;
        }
        else {
            return $bepath;
        }
    }

    public static function file($bepath, $once = false)
    {
        $path = self::path($bepath);

        if (file_exists($path)) {
            $once ? require_once($path) : require($path);

            $class = substr($bepath,strrpos($bepath,'/')+1);
            $absoluteClass = str_replace('/','_',$bepath);

            if (class_exists($class,false) && !class_exists($absoluteClass,false)) {
                class_alias($class,$absoluteClass);
            }
            return true;
        }
        elseif(count(spl_autoload_functions()) <= 1) {
            throw new Exception("File not found ($path)");
        }
    }

    public static function resource($resource_name, $directory)
    {
        return '/' . $directory . '/' . $resource_name;
    }

    public static function autoload($class) {

    }
}

spl_autoload_register(function($class){
    __include::file(preg_replace(array('/_/', '/\./'), array('/', ''), $class,2));
});
