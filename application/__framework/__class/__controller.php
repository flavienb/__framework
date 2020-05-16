<?php

/**
 * Description of __controller
 *
 * @author flavienb.com
 */
abstract class __controller
{
    /**
     * @var __view|null
     */
    protected $view = null;
    protected $__module;
    protected $__controllerName;
    protected $__rendered;
    protected $__token = null;

    public $__action = null;

    public final function __construct($view=null, $module=null, $action=null)
    {
        if ($view !== null) {
            $this->view = $view;
        } else {
            $this->view = new __view(null);
        }
        $this->__module = $module;

        $this->__action = $action;
    }

    public final function _init()
    {
        return $this->init();
    }

    protected final function generateToken()
    {
        $this->__token = sha1(uniqid(rand(), true) . __store::id());
        __store::set('__token', '__', $this->__token);
        $this->view->set('__token',$this->__token);
    }

    protected final function checkToken()
    {
        if (__store::get('__token', '__') !== __request::get('__token', '__')) {
            throw new Exception('__token error');
        }

        return true;
    }

    protected final function getToken() {
        if (!$this->__token) {
            $this->generateToken();
        }
        return $this->__token;
    }

    /**
     * Méthode appelée après l'instanciation du controller, à surcharger
     */
    abstract protected function init();

    /**
     * Méthode appelée à la toute fin de l'exécution du script, juste après le rendering
     */
    public function end()
    {
    }

    /**
     * Appeler une autre action du controller, à exécuter lors du init uniquement
     * @param <type> $bepath
     */
    protected final function __forward($action)
    {
        $this->__action = $action;
    }

    /**
     * Obtenir le nom simple du controller
     * @return <type>
     */
    protected final function getControllerName()
    {
        if (!$this->__controllerName) //On charge le nom pour éviter de devoir le refaire à chaque fois
            $this->__controllerName = strtolower(substr(get_class($this), 0, strrpos(get_class($this), '_')));
        return $this->__controllerName;
    }

    protected final function getModuleName()
    {
        return $this->__module;
    }

    /**
     * Attacher une vue supplémentaire au controller
     * @param <type> $view_name
     */
    protected final function render($bepath)
    {
        //La première fois qu'il est appelé, il définit la première vue à afficher
        if ($this->view) {
            if (!$this->__rendered) {
                $this->view->setView($bepath);
                $this->__rendered = true;
            } else
                $this->view->addView($bepath);
        } else
            throw new Exception("No View attached to this controller");
    }

    protected final function renderOnly($bepath)
    {
        $this->view->removeLayout();
        $this->__rendered = false;
        $this->render($bepath);
    }

    protected final function resetRender()
    {
        $this->__rendered = false;
    }

    protected function getClosure($class,$method) {
        $class = new ReflectionClass($class);
        $method = $class->getMethod($method);
        $albumClass = $class->newInstanceWithoutConstructor();
        return $method->getClosure($albumClass)->bindTo($this);
    }
}
