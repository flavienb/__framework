<?php

class example_model_user extends __model
{
    protected $database = 'MY_DATABASE'; // configuration specified in database.php conf file

    public function init()
    {
        // method called before any other
    }

    public function insertUser($login, $password, $firstname, $lastname)
    {
        $prep = $this->connexion->prepare("
            INSERT INTO user
            (email, password, firstname, lastname, date_create)
            VALUES
             (:email, :password, :firstname, :lastname, UNIX_TIMESTAMP())");
        $prep->execute(array(
            'email' => $login,
            'password' => $password,
            'firstname' => $firstname,
            'lastname' => $lastname
        ));
        return $this->connexion->lastInsertId();
    }
}
