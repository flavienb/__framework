<?php

class example_controller_locale extends __controller
{
    public function init() {
        // Before using the __locale module you should configure it in application.php conf file
        __locale::init('fr');
    }

    public function helloAction() {
        $this->view->hello = __locale::get('hello_world');
    }

    public function inViewAction() {

    }
}
