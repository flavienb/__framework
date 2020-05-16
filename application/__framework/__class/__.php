<?php
/**
 * @author flavienb.com
 */

final class __
{
    public static function htmlcss($cssname, $sufix = '')
    {
        return "<link rel='stylesheet' type='text/css' href='$cssname' />\n";
    }

    public static function htmljs($jsname, $sufix = '')
    {
        return "<script type='text/javascript' language='javascript' src='$jsname'></script>\n";
    }

    public static function redirect($bepath)
    {
        __store::save();
        header("location:$bepath");
        die();
    }

    public static function encode(&$str)
    {
        switch (__config::get('DEFAULT_ENCODING')) {
            case 'UTF-8' :
                $str = utf8_encode($str);
                break;
            case 'ISO-8859-1' :
                $str = utf8_decode($str);
                break;
            default :
                $str = utf8_encode($str);
        }

        return $str;
    }

    public static function dump($var)
    {
        echo '<pre>';
        var_dump($var);
        echo '</pre>';
    }

    // Execute POST request (needs Curl to be installed)
    public static function post($url, $data = array())
    {
        $fields_string = '';
        foreach ($data as $key => $value) {
            $fields_string .= $key . '=' . $value . '&';
        }
        rtrim($fields_string, '&');

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, count($data));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);

        curl_close($ch);

        return $result;
    }

    public static function getRealIpAddr()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) { //check ip from share internet
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) { //to check ip is pass from proxy
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } else {
            $ip = null;
        }
        return $ip;
    }

    public static function say($id, $tokens = array(), $module_name = null)
    {
        echo __locale::markup($id, $tokens, $module_name);
    }

    public static function bad_request($message = '')
    {
        if (__config::get('APPLICATION_STATE') != 'development') {
            __framework::jsonResponse(array('status' => 1, 'error' => 400, 'message' => 'Bad Request: ' . $message));
            header('HTTP/1.0 400 Bad Request');
            throw new Exception('Bad Request: ' . $message);
        } else {
            header('HTTP/1.0 400 Bad Request');
            throw new Exception('Bad Request: ' . $message);
        }
    }

    public static function unauthorized($message = '')
    {
        if (__config::get('APPLICATION_STATE') != 'development') {
            __framework::jsonResponse(array('status' => 1, 'error' => 401, 'message' => '401 Unauthorized: ' . $message));
            header('HTTP/1.0 401 Unauthorized');
            throw new Exception('401 Unauthorized: ' . $message);
        } else {
            header('HTTP/1.0 401 Unauthorized');
            throw new Exception('401 Unauthorized: ' . $message);
        }
    }

    static public function randomId()
    {
        return bin2hex(openssl_random_pseudo_bytes(32));
    }
}

class __StopException extends Exception {}

?>
