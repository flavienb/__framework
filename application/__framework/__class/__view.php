<?php
/**
 * @author flavienb.com
 */

/**
 * Description of _view
 *
 * @author flavien
 */
final class __view
{
    //Contenu
    private $_view_tab = array(); //Permet de connaître le nom du fichier phtml
    private $_data = array();

    //Paramètres liés aux layout
    private $layoutPath;
    private $nolayout = false;
    private $noCache = false;
    private $cache_path;


    function __construct($bepath)
    {
        $this->addView($bepath);
    }

    /**
     * Ajouter des vues à afficher à l'aide de la méthode render() du controller
     * @param <type> $view_name
     * @param <type> $controller_name
     */
    public function addView($bepath)
    {
        $this->_view_tab[] = $bepath;
    }

    /**
     * Définir la premiere vue à afficher à l'aide de la méthode render() du controller
     * @param <type> $view_name
     * @param <type> $controller_name
     */
    public function setView($bepath)
    {
        $this->unsetView();
        $this->addView($bepath);

    }

    /**
     * Supprimer toutes les vues à afficher
     */
    public function unsetView()
    {
        $this->_view_tab = array();
    }


    /**
     * Lancer le rendering html (layout et vues)
     */
    public function display()
    {
        $output = '';

        if (__config::get('CACHE_ENABLE_VIEW') && !$this->noCache) {
            $viewKey = $this->getCacheKey();
            $output = __cache::fetch($viewKey);
        }

        if (empty($output)) {
            ob_start();

            if (!$this->nolayout && $this->layoutPath) {
                $this->displayLayout();
            } else {
                $this->displayViews();
            }
            $output = ob_get_clean();

            if (class_exists('__locale', false) && __locale::isInitialized()) {
                $output = __locale::processHTML($output);
            }
        }

        if (__config::get('CACHE_ENABLE_VIEW') && !$this->noCache) {
            __cache::add($viewKey, $output, __config::get('CACHE_TTL_VIEW'));
            // Expire in the future
            $ttl = __config::get('CACHE_TTL_VIEW') ? __config::get('CACHE_TTL_VIEW') : __cache::getTTL();
            header('Cache-Control: PUBLIC, max-age=' . $ttl . ', must-revalidate');
            header('Expires: ' . gmdate('r', ($ttl > time() ? $ttl : time() + $ttl)) . ' GMT');
        }

        echo $output;
    }

    public function cached()
    {
        if (__config::get('CACHE_ENABLE_VIEW') && !$this->noCache) {
            return __cache::exists($this->getCacheKey());
        }
        return false;
    }

    public function getCacheKey()
    {
        return md5($_SERVER['REQUEST_URI'] .__request::getURI());
    }

    public function noCache() {
        $this->noCache = true;
    }

    /**
     * Obtenir le layout
     * @return mixed
     */
    public function getLayout()
    {
        return $this->layoutPath;
    }

    /**
     * Obtenir les vues enregistrées
     */
    public function getViews()
    {
        return $this->_view_tab;
    }

    /**
     * Permet à la vue en elle même d'afficher le contenu d'une autre vue
     * @param <type> $bepath
     */
    private function show($bepath)
    {
        $path = __include::path($bepath);
        if (file_exists($path)) {
            include($path);
        } else {
            throw new Exception('View not found : ' . $path);
        }
    }


    /**
     * Lancer l'affichage d'une vue
     */
    private function displayView($bepath)
    {
        $this->show($bepath);
    }

    /**
     * Obtenir l'affichage temporaire dans une variable
     */
    public function getDisplay($view = "")
    {
        ob_start();

        if (!$view) {
            $this->display();
        } else {
            $this->displayView($view);
        }

        $output =  ob_get_clean();

        if (class_exists('__locale', false) && __locale::isInitialized()) {
            $output = __locale::processHTML($output);
        }

        return $output;
    }

    /**
     * Lancer l'affichage des vues
     */
    private function displayViews()
    {
        for ($i = 0; $i < count($this->_view_tab); ++$i) {
            $this->show($this->_view_tab[$i]);
        }
    }

    //--------------------------------------------
    //------------Layouts-------------------------

    public function setLayout($layout_bepath)
    {
        $this->layoutPath = $layout_bepath;
    }

    /**
     * Supprime le layout défini
     */
    public function removeLayout()
    {
        unset($this->layoutPath);
    }

    /**
     * Désactive l'affichage du layout, même si celui-ci est défini
     */
    public function noLayout($b = true)
    {
        $this->nolayout = $b;
    }

    /**
     * Lancer l'affichage du layout
     */
    private function displayLayout()
    {
        $this->js = $this->processFiles(array_merge((array)__config::get('VIEW_JS'), (array)$this->js),'js');
        $this->css = $this->processFiles(array_merge((array)__config::get('VIEW_CSS'), (array)$this->css),'css');

        $this->show($this->layoutPath);
    }

    private function processFiles($files,$fileType) {
        $result = array();

        //On récupère les fichiers externe
        $externalFiles = array_filter($files,function($file){
            if (!is_array($file)) {
                return  strpos($file,'http') === 0;
            } else {
                return false;
            }
        });

        foreach((array)$files as $key => $file) {
            if (is_array($file)) {
                $result = array_merge($result,$this->processFiles($file,$fileType));
            } else {
                if (substr($file,0,4) != 'http') {
                    //Si ce n'est pas un fichier externe
                    //on traite le fichier et on ajoute son chemin absolue
                    if (substr($file,0,1) != '/') {
                        //Par défaut le fichier est dans le répertoire de son type
                        $file = PUBLIC_PATH . $fileType . '/' . $file;
                    } else {
                        $file = substr(PUBLIC_PATH,0,-1) . $file;
                    }

                    if (substr($file,-strlen($fileType)) != $fileType) {
                        //Si le fichier n'a pas d'extension, on lui ajoute
                        $file .= '.'.$fileType;
                    }

                    $result[] = $file;
                }
            }
        }


        //On retire le path absolu
        $result = array_map(function($file) { return str_replace(PUBLIC_PATH,'/',$file);},$result);

        //On place les fichiers externe en premier
        $result = array_merge($externalFiles,$result);

        return $result;
    }

    public function get($key)
    {
        if (isset($this->_data[$key])) {
            return $this->_data[$key];
        }
    }

    public function set($key, $value)
    {
        $this->_data[$key] = $value;
    }

    public function setData($data) {
        $this->_data = array_merge($this->_data,$data);
    }

    /**
     * Magic
     * @param $name
     * @param $value
     */
    public function __set($key, $value)
    {
        $this->_data[$key] = $value;
    }

    public function &__get($key)
    {
        return $this->_data[$key];
    }

    public function __isset($key) {
        return isset($this->_data[$key]);
    }

    public function getData()
    {
        return $this->_data;
    }

}

?>
