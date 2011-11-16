<?php
namespace SpiffyForm\Form\Element;
use Zend\Form\Element\Multi;

class Entity extends Multi
{
    protected $helpers = array(
        'default'          => 'formSelect',
        'multiple'         => 'formSelect',
        'expanded'         => 'formRadio',
        'multipleExpanded' => 'formMultiCheckbox',
    );
    
    public function init()
    {
        $this->helper  = $this->helpers['default'];
        $this->options = $this->getOptions();
    }
    
    protected function getOptions()
    {
       return array(0 => 'test', 1 => 'test2', 2 => 'test3'); 
    }
}
