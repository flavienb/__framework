<?php

class example_controller_session extends __controller
{
    public function init() {
        // method called before any Action
    }

    /**
     * Add session data
     * @return bool
     */
    public function setAction() {
        __store::set('greeting', 'Hello World !');

        return false;
    }

    /**
     * Remove session data
     * @return boolean
     */
    public function deleteAction() {
        __store::remove('greeting');

        return false;
    }

    /**
     * Destroy session
     * @return boolean
     */
    public function destroyAction() {
        __store::destroy();

        return false;
    }

    /**
     * Fetch session data and pass it to the view
     */
    public function helloAction() {
       $this->view->hello = __store::get('greeting');
    }
}
