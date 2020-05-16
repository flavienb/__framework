<?php

class example_controller_session extends __controller
{
    public function init() {
        // method called before any Action
    }

    public function setAction() {
        __store::set('greeting', 'Hello World !');

        return false;
    }

    public function deleteAction() {
        __store::remove('greeting');

        return false;
    }

    public function helloAction() {
       $this->view->hello = __store::get('greeting');
    }
}
