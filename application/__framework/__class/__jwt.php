<?php
/**
 * @author flavienb.com
 */

class __jwt
{
    public static function parseHeader() {
        try {
            $header = __config::get('AUTHORIZATION_HEADER');
            if (!empty($_SERVER[$header])) {
                return self::checkToken($_SERVER[$header]);
            }
        }
        catch(Exception $ex) {
            __::bad_request($ex->getMessage());
        }
    }


    static public function generateToken($payload=array(), $noExpiration = false) {
        if (!__config::has('AUTHENTICATION_SECRET')) {
            throw new Exception('No JWT secret has been defined');
        }

        if (!$noExpiration) {
            $payload['exp'] = time() + __config::get('AUTHENTICATION_TIMEOUT');
        }

        $token  = JWT::encode(array_merge($payload,array('__date_create' => time())),base64_decode(__config::get('AUTHENTICATION_SECRET')),'HS256');
        return $token;
    }

    static public function checkToken($token) {
        try {
            return JWT::decode($token,base64_decode(__config::get('AUTHENTICATION_SECRET')),array('HS256','HS512'));
        }
        catch(Exception $ex) {
            throw $ex;
        }
    }
}
