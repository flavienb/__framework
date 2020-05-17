<?php

class example_controller_jwt extends __controller
{
    public function init() {
    }

    /**
     * Generate a JWT token with some data
     * And return it in a JSON response
     * @return array
     * @throws Exception
     */
    public function generateAction() {
        $data = ['username' => '__framework'];

        $token = __jwt::generateToken($data);

        return [
            'token' => $token
        ];
    }

    /**
     * Check if JWT token is valid and decode it
     * @return object
     * @throws Exception
     */
    public function checkAction() {
        $token = __request::get('token');

        try {
            $data = __jwt::checkToken($token);
            return $data;
        } catch(Exception $e) {
            __::unauthorized();
        }

        __::unauthorized();
    }
}
