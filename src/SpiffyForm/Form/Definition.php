<?php
namespace SpiffyForm\Form;
use SpiffyForm\Form\Manager;

abstract class Definition
{
    /**
     * @var object
     */
    protected $dataObject;
    
    /**
     * Constructor.
     * 
     * @var object $dataObject
     */
    public function __construct($dataObject = null)
    {
        $this->setDataObject($dataObject);
    }
    
    /**
     * Set the data object
     * 
     * @param object $dataObject
     * @return SpiffyForm\Form\Definition, provides fluent interface
     */
    public function setDataObject($dataObject)
    {
        $this->dataObject = $dataObject;
        return $this;
    }
    
    /**
     * Get the data object bound to this definition.
     * 
     * @return object
     */
    public function getDataObject()
    {
        return $this->dataObject;
    }
    
    /**
     * Build the definition.
     * 
     * @param SpiffyForm\Form\Manager $m
     */
    abstract public function build(Manager $m);
    
    /**
     * Get options for the definition.
     * 
     * @return array
     */
    abstract public function getOptions();
}
