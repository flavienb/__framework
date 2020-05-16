<?php
/**
 * @author flavienb.com
 */

/**
 * Class __cache
 * @method static add($key,$value,$ttl=null)
 * @method static store($key,$value,$ttl=null)
 * @method static fetch($key)
 * @method static delete($key)
 * @method static exists($key)
 */
final class __cache
{
    static private $useAPC = false;
    static private $cache = array();
    static private $cacheFile = null;
    static private $ttl = 120;
    static private $gc_ttl = 600;
    static private $lastCache = 0;
    static private $maxCacheSize = 524288; //500ko
    static private $isFull = false;
    static private $currentTime = 0;
    static private $initialized = false;
    static private $isModified = false;

    static public function getTTL()
    {
        return self::$ttl;
    }

    static public function isInitialized()
    {
        return self::$initialized;
    }

    static public function save()
    {
        if (self::$initialized) {
            if( !self::$useAPC) {
                if ((self::$lastCache + self::$gc_ttl) <= self::$currentTime) {
                    self::garbageCollector();
                }
                if (self::$isModified) {
                    return file_put_contents(self::$cacheFile, json_encode(
                        array(
                            'meta' => array('lastCache' => self::$currentTime),
                            'data' => self::$cache
                        )
                    ));
                }
            }
        }
    }

    static private function _start($module = null)
    {
        if (__config::exists('CACHE_TTL')) {
            self::$ttl = intval(__config::get('CACHE_TTL'));
        }

        if (__config::exists('CACHE_MAX_SIZE')) {
            self::$maxCacheSize = intval(__config::get('CACHE_MAX_SIZE'));
        }

        if (__config::get('CACHE_ENABLE_APC') && extension_loaded('apc') && ini_get('apc.enabled')) {
            self::$useAPC = true;
            self::$initialized = true;
        } elseif ($module && is_dir(APPLICATION_PATH . 'modules/' . $module)) {
            self::$cacheFile = APPLICATION_PATH . 'modules/' . $module . '/__cache';
            self::load($module);
            self::$initialized = true;
        }

        self::$currentTime = time();
    }

    static private function _add($key, $value, $ttl = null)
    {
        if (self::$useAPC) {
            return apc_add($key, $value, $ttl ? $ttl : self::$ttl);
        } else {
            if (!self::exists($key)) {
                self::store($key, $value, $ttl);
            }
        }
    }

    static private function _store($key, $value, $ttl = null)
    {
        if (self::$useAPC) {
            return apc_store($key, $value, $ttl ? $ttl : self::$ttl);
        } elseif (!self::$isFull) {
            self::$cache[$key] = array(
                'time' => self::$currentTime + ($ttl ? $ttl : self::$ttl),
                'value' => $value
            );
            self::$isModified = true;
        }
    }

    static private function _fetch($key)
    {
        if (self::$useAPC) {
            return apc_fetch($key);
        } elseif (self::exists($key)) {
            return self::$cache[$key]['value'];
        }
    }

    static private function _delete($key)
    {
        if (self::$useAPC) {
            return apc_delete($key);
        } else {
            unset(self::$cache[$key]);
            self::$isModified = true;
        }
    }

    static private function _exists($key)
    {
        if (self::$useAPC) {
            return apc_exists($key);
        } else {
            if (isset(self::$cache[$key]) && self::$cache[$key]['time'] > self::$currentTime) {
                return true;
            } else {
                self::delete($key);
                return false;
            }
        }
    }

    static private function load()
    {
        if (file_exists(self::$cacheFile)) {
            $rawData = file_get_contents(self::$cacheFile);
            $cache = json_decode($rawData, true);
            self::$cache = $cache['data'];
            self::$lastCache = $cache['meta']['lastCache'];
            if (strlen($rawData) > self::$maxCacheSize) {
                self::$isFull = true;
            }
        }
    }

    static private function garbageCollector()
    {
        foreach ((array)self::$cache as $key => $cache) {
            if ($cache['time'] <= self::$currentTime) {
                unset(self::$cache[$key]);
            }
        }
    }

    public static function __callStatic($method, $arguments)
    {
        if (method_exists('__cache', '_' . $method)) {
            if (!self::$initialized) {
                self::_start(__request::getModule());
            }
            return call_user_func_array(array('__cache', '_' . $method), $arguments);
        } else {
            throw new Exception("Call to undefined method");
        }
    }
}
