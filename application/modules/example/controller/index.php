<?php

class example_controller_index extends __controller
{
    public function init() {
        // method called before any Action
    }

    /**
     * Default action
     * Shows you how to pass data to the view
     * By default, it renders the view at path application/modules/example/view/index/index.phtml
     * where 'example' is the module name, 'view' is the view folder, 'index' is the controller name and the last 'index' is the action name
     * Browser URL: http://127.0.0.1/
     */
    public function indexAction() {
        $this->view->message = 'Hello world';
    }

    /**
     * Returning JSON data
     * Browser URL: http://127.0.0.1/json
     * @return array
     */
    public function jsonAction() {
        return [
            'id' => 1,
            'product' => '__framework'
        ];
    }

    /**
     * Rendering a view at custom path
     * @throws Exception
     */
    public function customViewAction() {
        $this->render('example/view/custom');
    }

    /**
     * Shows you how to nest views
     * Have a look at the view located at application/modules/example/view/index/parentView.phtml
     */
    public function parentViewAction() {

    }

    /**
     * Shows you how to embed your views in a layout
     */
    public function withLayoutAction() {
        $this->view->setLayout('example/layout/myLayout');
    }

    /**
     * Returning nothing, except a 200 response code
     * @return bool
     */
    public function noViewAction() {
        // processing only...

        return false;
    }
}
