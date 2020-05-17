<?php

class example_controller_mail extends __controller
{
    public function init() {
        // method called before any Action
    }

    /**
     * Send a basic email
     * @return bool
     */
    public function sendAction() {
       __mail::send('me@myself.com', 'you@example.org', 'title', 'me', 'hello world');

       return false;
    }

    /**
     * Send an HTML email fron a view with some data
     * @return bool
     */
    public function sendWithViewAction() {
        __mail::init('example/view/mail/example', array(
            'name' => 'Foo'
        ));

        __mail::send('me@myself.com', 'you@example.org', 'title', 'me');

        return false;
    }

}
