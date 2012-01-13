<?php
namespace SpiffyForm\Form;
use SpiffyForm\Form\Manager,
    Zend\Stdlib\Parameters;

abstract class Definition
{
    /**
     * Build the definition.
     * 
     * @param SpiffyForm\Form\Manager $m
     */
    abstract public function build(Manager $m);
    
    /**
     * After the initial form is generated you can use post build to add 
     * additional form elements or set options.
     * 
     * @param SpiffyForm\Form\Manager $f
     */
    public function postBuild(Manager $m)
    {}
    
    /**
     * Additional validation for the form definition if required.
     * 
     * @param Zend\Stdlib\Parameters $params
     * @param Zend\Form\Form         $form
     */
    public function isValid(Parameters $params, $form)
    {
        return $form->isValid($params->toArray());
    }
    
    /**
     * Get options for the definition.
     * 
     * @return array
     */
    public function getOptions()
    {
        return array();
    }
}
