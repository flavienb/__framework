<?php

class example_controller_index extends __controller
{
    public function init() {
        // method called before any Action
    }

    public function indexAction() {
        $this->view->message = 'Hello world';
    }

    public function jsonAction() {
        return [
            'id' => 1,
            'product' => '__framework'
        ];
    }

    public function customViewAction() {
        $this->render('example/view/custom');
    }

    public function parentViewAction() {

    }

    public function withLayoutAction() {
        $this->view->setLayout('example/layout/myLayout');
    }

    public function noViewAction() {
        // processing only...

        return false;
    }
}
