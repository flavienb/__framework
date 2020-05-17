<?php

require(FRAMEWORK_PATH . '__class/__framework.php');
require(FRAMEWORK_PATH . '__class/__controller.php');
require(FRAMEWORK_PATH . '__class/__model.php');
require(FRAMEWORK_PATH . '__class/__view.php');
require(FRAMEWORK_PATH.'__class/__include.php');
require(FRAMEWORK_PATH.'__class/__.php');
require(FRAMEWORK_PATH.'__class/__registry.php');
require(FRAMEWORK_PATH.'__class/__config.php');
require(FRAMEWORK_PATH.'__class/__database.php');
require(FRAMEWORK_PATH.'__class/__localization.php');
require(FRAMEWORK_PATH.'__class/__navigation.php');
require(FRAMEWORK_PATH.'__class/__request.php');
require(FRAMEWORK_PATH.'__class/__store.php');
require(FRAMEWORK_PATH.'__class/__cookie.php');
require(FRAMEWORK_PATH.'__class/__cache.php');
require(APPLICATION_PATH.'config/database.php');
require(FRAMEWORK_PATH.'__class/__authentication.php');
require(FRAMEWORK_PATH.'__class/__action.php');
require(FRAMEWORK_PATH.'__class/__mail.php');
require(FRAMEWORK_PATH.'__class/__jwt.php');
if(file_exists(FRAMEWORK_PATH.'__lib/vendor/autoload.php')) {
    require(FRAMEWORK_PATH.'__lib/vendor/autoload.php');
}

