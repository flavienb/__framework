<?php

class example_controller_mail extends __controller
{
    public function init() {
        // method called before any Action
    }

    public function sendAction() {
       __mail::send('me@myself.com', 'you@example.org', 'title', 'me', 'hello world');

       return false;
    }

    public function sendWithViewAction() {
        __mail::init('example/view/mail/example', array(
            'name' => 'Foo'
        ));

        __mail::send('me@myself.com', 'you@example.org', 'title', 'me');

        return false;
    }

}
