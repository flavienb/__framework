<?php
/**
 * @author flavienb.com
 */

final class __tableHtml
{

    private $headersTab = array();
    private $bodysTab = array();
    private $footersTab = array();
    private $class;
    private $title = "";

    public function addHeader($header)
    {
        $this->headersTab[] = $header;
    }

    public function addBody($body)
    {
        $this->bodysTab[] = $body;
    }

    public function addFooter($footer)
    {
        $this->footersTab[] = $footer;
    }

    public function getHeaders()
    {
        return $this->headersTab;
    }

    public function getBodys()
    {
        return $this->bodysTab;
    }

    public function getFooters()
    {
        return $this->footersTab;
    }

    public function addClassNameToColumn($key, $classname)
    {
        if (($headers = $this->getHeaders()) && ($rows = $headers[0]->getRows()) && ($cell = $rows[0]->getCell($key))) {
            $index = $cell->getIndex();
            $this->addClassNameToZone($this->getHeaders(), $index, $classname);
            $this->addClassNameToZone($this->getBodys(), $index, $classname);
            $this->addClassNameToZone($this->getFooters(), $index, $classname);
        }
    }

    private function addClassNameToZone($zone, $index, $classname)
    {
        foreach ((array)$zone as $header) {
            foreach ((array)$header->getRows() as $row) {
                $cells = $row->getCellsIndex();
                $cells[$index]->addClassName($classname);
            }
        }
    }

    public function getContentType($index)
    {
        if (($headers = $this->getHeaders()) && ($rows = $headers[0]->getRows()) && ($cell = $rows[0]->getCellByIndex($index))) {
            return $cell->getContentType();
        }
    }


    public function setTile($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function sort($identifier, $sens = 'desc')
    {
        if (($headers = $this->getHeaders()) && ($header_rows = $headers[0]->getRows()) && ($cell = $header_rows[0]->getCellByIdentifier($identifier))) {

            $col_index = $cell->getIndex();
            $type = $cell->getContentTypeForSort();
            foreach ((array)$this->getBodys() as $body) {
                foreach ((array)$body->getRows() as $row_index => $row) {
                    $columns[$row_index] = $row->getCellByIndex($col_index)->getContent();
                }

                switch ($type) {
                    case 'string' :
                        $contentype = SORT_STRING;
                        break;
                    default :
                        $contentype = SORT_NUMERIC;
                        break;
                }
                if ($sens == 'desc')
                    arsort($columns, $contenttype);
                else
                    asort($columns, $contenttype);
                foreach ((array)$columns as $row_index => $value) {
                    $rowsTab[] = $body->rowsTab[$row_index];
                }
                $body->rowsTab = $rowsTab;
            }
        }
    }

}

class __tableZone
{

    public $rowsTab = array();

    public function addRow($row)
    {
        $this->rowsTab[] = $row;
    }

    public function getRows()
    {
        return $this->rowsTab;
    }

}

class __tableRow
{

    protected $cellsTab = array();
    protected $cellsTabIndex = array();
    protected $params = array();
    protected $class;
    protected $lastAddedCell;
    protected $className;

    public function addCell($cell, $identifier = "")
    {
        if ($identifier && !is_numeric($identifier)) {
            $cell->setIdentifier($identifier);
            $this->cellsTab[$identifier] = $cell;
        } else
            $this->cellsTab[] = $cell;

        $cell->setRow($this);
        $index = count($this->cellsTab) - 1;
        $this->cellsTabIndex[$index] = $cell;
        $cell->setIndex($index);

        $this->lastAddedCell = $cell;

        return $this;
    }

    public function getLastCell()
    {
        return $this->lastAddedCell;
    }

    public function getCells()
    {
        return $this->cellsTab;
    }

    public function getCellsIndex()
    {
        return $this->cellsTabIndex;
    }

    public function getCell($identifier)
    {
        return $this->cellsTab[$identifier];
    }

    public function getCellByIndex($index)
    {
        return $this->cellsTabIndex[$index];
    }

    public function getCellByIdentifier($identifier)
    {
        return $this->cellsTab[$identifier];
    }

    public function removeCell($identifier)
    {
        if (($index = $this->cellsTabIndex[$identifier]) && $this->cellsTab[$identifier]) {
            unset($this->cellsTabIndex[$index]);
            unset($this->cellsTab[$identifier]);
        }
    }

    public function addClassName($className)
    {
        $this->className = $className;
        return $this;
    }

    public function getClassName()
    {
        return $this->className;
    }

    public function addParam($name, $value)
    {
        $this->params[$name] = $value;
        return $this;
    }

    public function getParams()
    {
        return $this->params;
    }

}

class __tableHeader extends __tableZone
{

}

class __tableBody extends __tableZone
{

}

class __tableFooter extends __tableZone
{

}

class __tableCell
{

    protected $content;
    //contenu
    protected $align = 'right';
    //alignement
    protected $classname;
    //classe css
    protected $number_format;
    protected $unit;
    protected $identifier;
    protected $index;
    protected $row;
    protected $options;
    protected $attributes;
    protected $params;

    //unité (€,%...)
    public function __construct($content, $identifier = "")
    {
        $this->content = $content;
        $this->identifier = $identifier;
    }

    public function setAlign($align)
    {
        $this->align = $align;
        return $this;
    }

    public function setClassName($classname)
    {
        $this->classname = $classname;
        return $this;
    }

    public function addClassName($classname)
    {
        $this->classname = ($this->classname) ? $this->classname . " $classname" : $classname;
        return $this;
    }

    public function setAttribute($attribute, $value)
    {
        $this->attributes[$attribute] = $value;
        return $this;
    }

    public function addParam($param, $value)
    {
        $this->params[$param] = $value;
        return $this;
    }

    public function setContent($content)
    {
        $this->content = $content;
    }

    public function setNumberFormat($number_format)
    {
        $this->number_format = $number_format;
        return $this;
    }

    public function setUnit($unit)
    {
        $this->unit = $unit;
        return $this;
    }

    public function setIdentifier($identifier)
    {
        if ($this->identifier && $this->row->getCell($this->identifier) == $this) {
            $this->row->removeCell($identifier);
            $this->row->addCell($this, $identifier);
        } else
            $this->identifier = $identifier;
    }

    public function setIndex($index)
    {
        if ($this->index && ($cells = $this->row->getCells()) && $cells[$index] == $this) {
            $this->row->removeCell($index);
            $this->row->addCell($this);
        } else
            $this->index = $index;
    }

    public function setRow($row)
    {
        $this->row = $row;
    }

    public function setOptions($options)
    {
        $this->options = $options;
    }

    public function getAlign()
    {
        return $this->align;
    }

    public function getNumberFormat()
    {
        return $this->number_format;
    }

    public function getClassName()
    {
        return $this->classname;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function getUnit()
    {
        return $this->unit;
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }

    public function getIndex()
    {
        return $this->index;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function getParams()
    {
        return $this->params;
    }

}

class __tableHeaderCell extends __tableCell
{

    protected $align = 'center';
    protected $contentType = 'number'; //utile pour l'affichage dans la vue
    protected $contentTypeForSort = 'number'; //utile pour le sort javascript

    function setContentType($contentType)
    {
        switch ($contentType) {
            case 'string' :
                $this->contentType = 'string';
                $this->contentTypeForSort = 'string';
                break;
            case 'date' :
                $this->contentType = 'date';
                $this->contentTypeForSort = 'number';
                break;
        }
        return $this;
    }

    function getContentType()
    {
        return $this->contentType;
    }

    function getContentTypeForSort()
    {
        return $this->contentTypeForSort;
    }

}

class __tableDataCell extends __tableCell
{

}

?>
