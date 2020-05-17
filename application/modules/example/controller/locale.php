<?php

class example_controller_locale extends __controller
{

    /**
     * Initialize the localization language
     * Before using the __locale module you should configure it in application.php conf file and set up you database accordingly
     */
    public function init() {
        // Before using the __locale module you should configure it in application.php conf file
        __locale::init('fr');
    }

    /**
     * Pass a translated string to the view
     */
    public function helloAction() {
        $this->view->hello = __locale::get('hello_world');
    }

    /**
     * Display a translated string, directly from the view
     */
    public function inViewAction() {

    }
}
