<?php

class example_controller_jwt extends __controller
{
    public function init() {
    }

    public function generateAction() {
        $data = ['username' => '__framework'];

        $token = __jwt::generateToken($data);

        return [
            'token' => $token
        ];
    }

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
