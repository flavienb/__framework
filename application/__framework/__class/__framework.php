<?php
/**
 * @author flavienb.com
 */

final class __framework {

    private static $module = null;
    private static $controller = null;
    private static $action = null;
    private static $argv = null;


    public static function init($onlyInit = false) {
        global $argv;
        ob_start('ob_gzhandler');
        set_exception_handler(array('__framework','exceptionHandler'));

        self::$argv = $argv;
        self::loadConfig($argv);
        self::setErrorHandler();

        if (__config::get('ACTION_ENABLE') && isset($argv[1])) {
            __action::init($argv);
        }

        if (!$onlyInit) {
            self::execute();
        }
    }

    private static function loadConfig($argv=null) {
        __config::init(require(APPLICATION_PATH . 'config/application.php'));
        __request::init($argv);

        self::$module = __request::getModule();

        if (($module_config_path = __include::path(self::$module . '/config')) && file_exists($module_config_path)) {
            include($module_config_path);
        }

        self::$module = __request::getModule();
        self::$controller = __request::getController();
        self::$action = __request::getAction();
    }

    private static function setErrorHandler() {
        if (false === __config::get('DISPLAY_ERRORS')) {
            error_reporting(E_ERROR | E_WARNING | E_PARSE);
            ini_set('display_errors',0);
        }
        else {
            error_reporting(E_ALL);// ^ E_NOTICE););
            ini_set('display_errors',1);
        }
    }

    public static function exceptionHandler($exception) {
        if (ini_get('display_errors') > 0) {
            echo '<br/>---------------------------------------------------';
            echo '<br/> <b>Exception : </b> '.$exception->getMessage().'<br/>';
            echo '<b>File : </b>'.$exception->getFile().'<br/>';
            echo '<b>Line : </b>'.$exception->getLine().'<br/>';
            echo '<b>Trace : </b><pre>'.$exception->getTraceAsString().'</pre>';
            echo '---------------------------------------------------<br/><br/>';
        }
        if (__config::get('ERROR_MAIL')) {
            self::sendExceptionMail($exception);
        }
    }

    public static function sendExceptionMail($exception) {
        ob_start();
        echo '<br/>---------------------------------------------------';
        echo '<br/> <b>Exception : </b> '.$exception->getMessage().'<br/>';
        echo '<b>File : </b>'.$exception->getFile().'<br/>';
        echo '<b>Line : </b>'.$exception->getLine().'<br/>';
        echo '<b>Trace : </b><pre>'.$exception->getTraceAsString().'</pre>';
        echo '---------------------------------------------------<br/><br/>';
        echo $_SERVER['REQUEST_URI'].'<br>'.__::getRealIpAddr().'<br>'.$_SERVER['HTTP_USER_AGENT'];
        $message = ob_get_clean();
        __mail::send(__config::get('ERROR_MAIL_FROM'),__config::get('ERROR_MAIL_TO'),'Exception',__config::get('ERROR_MAIL_FROMNAME'),$message);

    }

    public static function jsonResponse($data) {
        echo __config::get('XSS_PREFIX_STRING').json_encode($data);
    }

    private static function execute($forwardHistory = array(),$view=null) {
        try {
            $bepath = self::$module . '/controller/' . self::$controller;
            $path = __include::path($bepath);

            if (!isset($forwardHistory[$path])) {
                $forwardHistory[$path] = 1; //to prevent forward loop

                if (file_exists($path)) {

                    include_once($path);

                    if (!$view) {
                        $view = new __view(self::$module . '/view/' . self::$controller . '/' . self::$action);
                    }

                    if ((($classname = __include::absolute_class($bepath)) && class_exists($classname,false))
                        || ($classname = self::$controller && class_exists($classname,false))) {

                        $controller = new $classname($view, self::$module, self::$action);

                        $actionReturn = $controller->_init();

                        if (is_null($actionReturn)) {
                            $action = $controller->__action.'Action';

                            if (method_exists($controller, $action)) {
                                $actionReturn = $controller->$action();
                                if (is_string($actionReturn)) {
                                    //On teste si on obtient un forward request en retour
                                    $forwardReq = explode('/',$actionReturn);
                                    if (count($forwardReq) == 3) {
                                        list(self::$module,self::$controller,self::$action) = $forwardReq;
                                        self::execute($forwardHistory,$view);
                                        return ;
                                    }
                                    else {
                                        throw new Exception("Error : incorrect forward request path ($actionReturn)");
                                    }
                                }
                                $endReturn = $controller->end();
                            }
                            else {
                                throw new Exception("Error : the \"$action\" action does not exist in \"$classname\" controller");
                            }

                            $return = !empty($endReturn)?$endReturn:$actionReturn;
                        }


                        if ($endReturn !== false && $actionReturn !== false) {

                            if (is_null($endReturn) && is_null($actionReturn)) {
                                $view->display();
                            } else {
                                $dataReturn = null;
                                if(is_array($endReturn) && is_array($actionReturn)) {
                                    $dataReturn = array_merge($endReturn,$actionReturn);
                                } elseif(is_object($endReturn) && is_object($actionReturn)) {
                                    $dataReturn = (object) array_merge((array) $endReturn, (array) $actionReturn);
                                } else {
                                    $dataReturn = $actionReturn;
                                }
                                if ($dataReturn) {
                                    self::jsonResponse($dataReturn);
                                }
                            }
                        }


                        __cache::save();
                        __store::save();

                        if (ob_get_level()) {
                            ob_end_flush();
                        }
                    }
                    else {
                        throw new Exception("Error : the controller does not exist (".self::$controller." $classname".")");
                    }
                }
                else {
                    throw new Exception('Error : the file does not exist '.$path);
                }
            }
            else {
                throw new Exception("Error : forward loop detected ($path)");
            }
        } catch(__StopException $exception) {
            // We should stop the execution
        }
    }

}

