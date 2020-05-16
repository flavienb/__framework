<?php

class example_controller_auth extends __controller
{
    /**
     * @var example_model_user
     */
    protected $datasource;

    public function init() {
        // initialize our model
        $this->datasource = new example_model_user();
    }

    public function loginAction() {
        $requiredParams = ['login', 'password'];

        if ($params = __request::checkParams($requiredParams)) {
            // Before using the __auth module you should configure it in application.php conf file
            $isAuthenticated = __auth::authenticate(__request::raw('login'), __request::raw('password'));

            if ($isAuthenticated) {
                return __auth::getInfos();
            }
        }

        __::unauthorized('Bad login or password');
    }

    public function logoutAction() {
        __auth::disconnect(true);

        return false;
    }

    public function privateAction() {
        if (__auth::isAuthenticated()) {
            return ['access_granted' => 1];
        }

        __::unauthorized();
    }

    public function signupAction() {
        $requiredParams = ['login', 'password'];
        $optionalParams = ['firstname', 'lastname'];

        if ($params = __request::checkParams($requiredParams, $optionalParams)) {
            $password = password_hash($params['password'], PASSWORD_DEFAULT);
            $user_id = $this->datasource->insertUser($params[ 'login'], $password, $params['firstname'], $params['lastname']);

            return ['user_id' => $user_id];
        }

        __::bad_request('Missing params');
    }
}
