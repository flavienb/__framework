<?php
/**
 * @author flavienb.com
 */


final class __database
{

    //Paramètres de connexion aux bases de données
    private static $databases = array();

    public static function connect($dbId)
    {
        try {
            if (isset(self::$databases[$dbId])) {
                if (!isset(self::$databases[$dbId]['connexion'])) {
                    switch (self::$databases[$dbId]['type']) {
                        case 'pdo'  :
                            self::$databases[$dbId]['connexion'] = new PDO(
                                self::$databases[$dbId]['string'],
                                self::$databases[$dbId]['login'],
                                self::$databases[$dbId]['password'],
                                array(
                                    PDO::ATTR_ERRMODE   =>  PDO::ERRMODE_EXCEPTION,
                                    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES UTF8',
                                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                                )
                            );
                            return self::$databases[$dbId]['connexion'];
                            break;
                        case 'odbc' :
                            $con = odbc_connect(self::$databases[$dbId]['string'], self::$databases[$dbId]['login'], self::$databases[$dbId]['password']);
                            self::$databases[$dbId]['connexion'] = new ODBC_connexion($con);
                            return self::$databases[$dbId]['connexion'];
                            break;
                    }
                } else {
                    return self::$databases[$dbId]['connexion'];
                }
            }
            else {
                throw new Exception("Database connection properties not found ($dbId)");
            }
        } catch (Exception $ex) {
            //On lance toutes les exceptions
            throw new Exception($ex->getMessage());
        }
    }

    public static function disconnect($dbId)
    {
        try {
            self::$databases[$dbId]['connexion'] = null;
        } catch (Exception $ex) {
            //On lance toutes les exceptions
            throw new Exception($ex->getMessage());
        }
    }

    public static function set($name, $string, $login, $password, $type = 'pdo')
    {
        self::$databases[$name] = Array(
            'string' => $string,
            'login' => $login,
            'password' => $password,
            'type' => $type
        );
    }
}

final class ODBC_connexion
{

    private $connexion;

    public function __construct($connexion)
    {
        $this->connexion = $connexion;
    }

    public function query($sql_query)
    {
        if ($result = @odbc_exec($this->connexion, $sql_query))
            return $result;
        else
            throw new Exception('Query Error in ODBC_connexion object : "<b>' . odbc_errormsg() . '</b>"');
    }

    public function fetch($sql_result)
    {
        $fetch = odbc_fetch_array($sql_result);
        return $fetch;
    }

}
