<?php
/**
 * @author flavienb.com
 */

// Password hashing API
require(FRAMEWORK_PATH . '__lib/password.php');
require(FRAMEWORK_PATH.'__lib/jwt.php');

final class __auth
{

    static private $connexion;
    static private $client;
    static private $custom_sql;
    static private $config = array();
    static private $fields;

    static private function connect()
    {
        self::$config = __config::get('AUTHENTICATION_CONFIG');
        self::$connexion = __database::connect(self::$config['DATABASE']);
        self::$custom_sql = !empty(self::$config['CUSTOM_SQL'])?self::$config['CUSTOM_SQL']:null;

        self::$config['FIELDS_SELECT'][] = self::$config['FIELD_PASSWORD'];
        self::$config['FIELDS_SELECT'][] = self::$config['FIELD_IDUSER'];

        $fields = array(self::$config['FIELD_IDUSER'],self::$config['FIELD_PASSWORD']);

        if (isset(self::$config['FIELDS_SELECT'])) {
            $fields = array_merge($fields,self::$config['FIELDS_SELECT']);
        }
        self::$fields = implode(',',$fields);
    }

    static private function setSession()
    {
        __store::set('__client', self::$client, '__');
    }

    static public function authenticate($username = null, $password = null, $custom_restrictions = '')
    {
        self::connect();
        $authentication = null;


        if ($username && $password) {
            $req = self::$connexion->prepare('SELECT ' . self::$fields . ' FROM ' . self::$config['TABLE']
                . ' WHERE ' . self::$config['FIELD_USERNAME'] . ' = :username '
                . self::$custom_sql .' '. $custom_restrictions
                . ' LIMIT 1');

            $req->execute(array(
                'username' => $username
            ));
            $result = $req->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                if (password_verify($password, $result[self::$config['FIELD_PASSWORD']])) {
                    $authentication = $result;
                }
            }
        }

        if ($authentication) {
            self::setAuthentication($authentication);
            return true;
        }

        return false;
    }

    static public function authenticateID($iduser, $custom_restrictions = '') {
        self::connect();
        $authentication = null;

        if ($iduser) {
            $req = self::$connexion->prepare('SELECT ' . self::$fields . ' FROM ' . self::$config['TABLE'] . ' WHERE ' . self::$config['FIELD_IDUSER'] . ' = :iduser ' . $custom_restrictions);
            $req->execute(array(
                'iduser' => $iduser
            ));
            $authentication =  $req->fetch(PDO::FETCH_ASSOC);
        }

        if ($authentication) {
            self::setAuthentication($authentication);
            return true;
        }

        return false;
    }

    static private function setAuthentication($authentication) {
        self::$client = $authentication;
        unset(self::$client[self::$config['FIELD_PASSWORD']]);
        self::setSession();
    }

    /**
     * Vérifier si l'utilisateur est authentifié à l'aide de sa session
     * @return <type>
     */
    static public function isAuthenticated()
    {
        //On essaye de s'authentifier sans username et password (action execute)
        if (self::$client || __action::isRequestGenuine()) { //Si on n'a pas rechargé la page on n'a pas besoin de chercher dans la session, on retourne true
            return true;
        } else if (__store::exists('__client', '__')) {
            self::$client = __store::get('__client', '__');
            return true;
        }
        self::disconnect(true); //dans les autres cas on se déconnecte
        return false;
    }

    /**
     * Obtenir le niveau de droits de l'utilisateur authentifié
     * @return <type>
     */
    static public function getRoleId()
    {
        if (self::isAuthenticated() && self::$client['role_id']) {
            return self::$client['role_id'];
        } else
            return '0'; //si on n'est pas authentifié, on retourne 1 (guest id)
    }

    /**
     * Obtenir les infos de l'utilisateur authentifié
     * @return <type>
     */
    static public function getInfos($reload = false)
    {
        if (self::isAuthenticated()) {
            self::connect();
            if ($reload) {
                $req = self::$connexion->prepare('SELECT ' . self::$fields . ' FROM ' . self::$config['TABLE'] . ' WHERE ' . self::$config['FIELD_IDUSER'] . ' = :iduser');
                $req->execute(array(
                    'iduser' => self::$client[self::$config['FIELD_IDUSER']]
                ));
                $authentication = $req->fetch(PDO::FETCH_ASSOC);
                self::setAuthentication($authentication);
            }

            return self::$client;
        }
        return array();
    }

    static public function disconnect($auth_only = false)
    {
        if ($auth_only) {
            __store::remove('__client', '__');
        } else {
            __store::destroy();
        }
        self::$client = null;
    }

    static public function generateToken($payload,$name=null) {
        if (!__config::has('AUTHENTICATION_SECRET')) {
            throw new Exception('No JWT secret has been defined');
        }
        $payload['exp'] = time() + __config::get('AUTHENTICATION_TIMEOUT');
        $token  = JWT::encode(array_merge($payload,array('__date_create' => time())),base64_decode(__config::get('AUTHENTICATION_SECRET')),'HS256');
        __cookie::set(($name?$name:'__jwt'),$token,array('expiration'=>__config::get('AUTHENTICATION_TIMEOUT'),'no_js' => true));
    }

    static public function hasToken($name=null) {
        return __cookie::has(($name?$name:'__jwt'));
    }

    static public function checkToken($name=null) {
        try {
            return JWT::decode(__cookie::get(($name?$name:'__jwt')),base64_decode(__config::get('AUTHENTICATION_SECRET')),array('HS256','HS512'));
        }
        catch(Exception $ex) {
            throw $ex;
        }
    }

    static public function deleteToken($name=null) {
        __cookie::delete(($name?$name:'__jwt'));
    }
}

class_alias('__auth', '__authentication');
