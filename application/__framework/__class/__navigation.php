<?php
/**
 * @author flavienb.com
 */

/**
 * Description of __controller
 *
 * @author flavien
 */
abstract class __navigation
{

    protected $links_array;
    protected $groups_array;


    public function __construct($database = "")
    {
        if ($database) {
            $this->connexion = __database::connect($database);
        }
        $this->init();
    }

    public function init()
    {
    }

    protected function addGroup($groupName, $group_identifier = null)
    {
        $group = new __navigation_Group($groupName);

        if ($group_identifier && !is_numeric($group_identifier)) {
            $this->groups_array[$group_identifier] = $group;
        } else {
            $this->groups_array[count($this->groups_array)] = $group;
        }

        return $group;
    }

    protected function getLastGroup()
    {
        return $this->groups_array[count($this->groups_array) - 1];
    }

    /**
     *
     * @param <__navigation_Link> $link
     * @param <String> $identifier  !!Ne doit pas être un numeric
     */
    protected function addLink($bepath, $link_identifier = null)
    {
        $link = new __navigation_Link($bepath);

        if ($link_identifier && !is_numeric($link_identifier)) {
            $this->links_array[$link_identifier] = $link;
        } else {
            $this->links_array[count($this->links_array)] = $link;
        }

        return $link;
    }


    protected function getLastLink()
    {
        $tab_keys = array_keys($this->links_array);
        return $this->links_array[$tab_keys[count($tab_keys) - 1]];
    }

    protected function setSelectedLink($link_identifier)
    {
        $link = $this->getLink($link_identifier);
        if ($link) {
            $link->setSelected();
        }
    }

    public function getLinks()
    {
        //On construit les href
        return $this->links_array;
    }

    public function getLink($link_identifier)
    {
        return $this->links_array[$link_identifier];
    }

    public function getGroups()
    {
        return $this->groups_array;
    }

    protected function getGroup($group_identifier)
    {

        return $this->groups_array[$group_identifier];
    }

}

class __navigation_Group extends __navigation
{
    protected $name;

    public function init()
    {
    }

    public function __construct($name)
    {
        $this->name = $name;
    }


    public function getName()
    {
        return $this->name;
    }
}

class __navigation_Link
{

    protected $isSelected = false;
    protected $name;
    protected $params_tab;
    protected $paths_tab;
    protected $text;
    protected $classNames_tab;
    protected $id;
    protected $blank;


    protected $bepath;

    /**
     *
     * @param <String> $module
     * @param <String> $controller
     * @param <String> $action
     */
    public function __construct($bepath)
    {
        __include::formatBepath($bepath);
        $this->bepath = $bepath;
    }


    public function getHTML()
    {
        $class = null;
        foreach ((array)$this->classNames_tab as $className) {

            $class .= $className . ' ';
        }

        if ($class) {
            $class = "class='$class'";
        }

        if ($this->id) {
            $id = "id='" . $this->id . "'";
        }

        if ($this->blank) {
            $target = "target='_blank'";
        }

        $url = $this->getUri();

        $text = $this->text;

        return "<a href='$url' $target $class $id>$text</a>";
    }

    public function addPath($path)
    {
        $this->paths_tab[] = $path;
        return $this;
    }

    public function addLang()
    {
        $this->paths_tab[] = __locale::getLang();
        return $this;
    }

    public function addClass($class)
    {
        $this->classNames_tab[] = $class;
        return $this;
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function addParam($identifier, $content)
    {
        if (is_array($content)) {
            array_walk_recursive($content, '__navigation_Link::formatData');
            $this->params_tab[$identifier] = $content;
        } else
            $this->params_tab[$identifier] = urlencode($content);
        return $this;
    }

    public function addAllParams()
    {
        foreach ((array)__request::getAll() as $key => $value) {
            $this->addParam($key, $value);
        }
        return $this;
    }

    static private function formatData(&$item, $key)
    {
        $item = urlencode($item);
    }

    public function setSelected()
    {
        $this->isSelected = true;
        return $this;
    }

    public function setBlank()
    {
        $this->blank = true;
    }


    public function isSelected()
    {
        return $this->isSelected;
    }

    public function setText($text)
    {
        $this->text = $text;
        return $this;
    }

    public function getText()
    {
        return $this->text;
    }

    /*
     * Générer les paramètres du lien sans le ? au début
     *
     */
    public function getUri()
    {
        $strlink = '';
        //On ajoute les paths
        foreach ((array)$this->paths_tab as $path) {
            $strlink .= "/$path";
        }

        //On ajoute le bepath
        $strlink .= $this->bepath;
        if (count($this->params_tab) > 0) {
            $strlink .= '?' . http_build_query((array)$this->params_tab, '', '&amp;');
        }

        return $strlink;

    }

}

?>
