<?php
namespace SpiffyForm\Form;
use SpiffyForm\Form\Manager;

abstract class Type
{
    protected $_dataObject;
    
    public function __construct($dataObject = null)
    {
        $this->_dataObject = $dataObject;
    }
    
    public function getDataObject()
    {
        return $this->_dataObject;
    }
    
    abstract public function build(Manager $m);
    
    abstract public function getOptions();
}