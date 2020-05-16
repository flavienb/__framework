<?php

/**
 * @author flavienb.com
 */

final class __cookie
{
    const TIME_SESSION = null;
    const TIME_DAY = 86400;
    const TIME_WEEK = 604800;
    const TIME_MONTH = 2592000;
    const TIME_HALF_YEAR = 15811200;
    const TIME_YEAR = 31536000;
    const TIME_LIFE = -1;

    /**
     * Returns true if there is a cookie with this name.
     *
     * @param string $name
     * @return bool
     */
    public static function exists($name)
    {
        return isset($_COOKIE[$name]);
    }

    /**
     * Returns true if there no cookie with this name or it's empty, or 0,
     *
     * @param string $name
     * @return bool
     */
    public static function isEmpty($name)
    {
        return empty($_COOKIE[$name]);
    }

    /**
     * Get the value of the given cookie.
     * @param string $name
     * @param string $default
     * @return mixed
     */
    public static function get($name)
    {
        if (isset($_COOKIE[$name])) {
            $return = $_COOKIE[$name];
            if (is_array($return)) {
                array_walk_recursive($return, '__request::formatData');
            } else {
                $return = __request::formatData($return);
            }
            return $return;
        }
    }

    /**
     * Check if cookie exists
     * @param $name
     * @return bool
     */
    public static function has($name)
    {
        return isset($_COOKIE[$name]);
    }

    /**
     * Set a cookie
     *
     * @param string $name
     * @param string $value
     * @param mixed $options array(expiration,path,domain,https_only,no_js)
     * @return bool
     */
    public static function set($name, $value, $options=array())
    {
        $defaults = array(
            'expiration' =>  self::TIME_YEAR,
            'path' =>  '/',
            'domain' =>  null,
            'https_only' =>  false,
            'no_js' =>  false
        );

        $options = array_merge($defaults,$options);

        if ($options['expiration'] === self::TIME_LIFE) {
            $options['expiration'] = 1893456000; // Lifetime = 2030-01-01 00:00:00
        } elseif (is_numeric($options['expiration'])) {
            $options['expiration'] += time();
        } else {
            $options['expiration'] = strtotime($options['expiration']);
        }

        $retval = @setcookie($name, $value, $options['expiration'], $options['path'], $options['domain'], $options['https_only'], $options['no_js']);

        if ($retval) {
            $_COOKIE[$name] = $value;
        }

        return $retval;
    }

    /**
     * Delete a cookie.
     *
     * @param string $name
     * @param string $path
     * @param string $domain
     * @return bool
     */
    public static function delete($name, $options=array())
    {
        $defaults = array(
            'path' =>  '/',
            'domain' =>  null
        );

        $options = array_merge($defaults,$options);

        $retval = setcookie($name, '', time() - 3600, $options['path'], $options['domain']);

        unset($_COOKIE[$name]);

        return $retval;
    }
}
