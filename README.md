# __framework
### Easy PHP MVC framework

I crafted this framework years ago when Laravel was still a baby because I needed to add some stucture to my PHP projects, without the hassle of
a giant framework like Zend.

Today, **__framework** is still being used in production in many business projects I worked for.

Feel free to test it, use it, and give feeback or send pull requests.

### Features

Designed to be easy to learn and deploy. It covers only basic and essential concepts.

- MVC pattern
- No routes to setup (automatic routing)
- No middleware
- No templating engine
- HTML rendering with views, nested views and layouts
- Autoload files (no require/include needed)
- JSON support
- JWT support
- Built in authentication module
- Mailing nodule
- Localization module

### Requirements

-	PHP 5.6+ with PDO extension
-   MySQL / MariaDB
-   HTTP server (Apache / Nginx)

### Getting started

It should take only a few minutes to get started with **__framework**, so that you can quick focus on development.

-   Have your HTTP server (Apache / Nginx) ready
-	Clone or download this repo into a new directory - say `myproject/`.
-	Configure a new virtual host in your HTTP server, and set the root path to `myproject/public`
-   If Apache is used, make sure the rewrite mod is enabled (``# a2enmod rewrite``) and allow .htaccess override (`AllowOverride All`)
-	Reload you HTTP server and start playing

### Database

If you want to quickly test all the features, you can create the necessary tables by importing the .sql file located at `application/modules/example/database_structure.sql`.
All these tables are only here to make the example module working, you can customize them as you wish and edit the `application/config/application.php` config file accordingly.

Also, don't forget to set up your database config and credentials in the `application/config/database.php` file.

### Folder Structure

- `public/` - the root path of your web server, contains public files only (images, css, js ...)
- `application/__framework` - the heart of the framework, you shouldn't have to care about it, unless you want to tweak it
- `application/config/` - all the default config files, have a look and customize it
- `application/lib/` - where you can install your external dependencies (``# composer require ...``)
- `application/modules/` - this is where your code should go ! Have a look at the example module

### How it works

Your PHP code should go in the `application/modules/` folder.
Here you will find an example module with 3 main folders : model, view, controller (MVC).

Let's open the index.php controller:

```php
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
```

When you write your own controllers, please follow the naming convention for the class name and the public methods.

Each public method name ending with *Action* is an endpoint of your application. That means you can execute it with a simple HTTP request.

For example, if we wanted to execute the *jsonAction* method from our browser, the URL would be http://127.0.0.1/example/index/json.
Where **example** is the module name, **index** is the controller name, and **json** is the action name.

You could also try to execute http://127.0.0.1/json and you should get the same output, because **example** is our application default module,
and **index** is the default controller, so you don't need to specify them if the URL. You can change these defaults in the ``application.php`` config file.

Endpoints are accessible from both GET and POST requests.
