<?php
namespace SpiffyForm\Form;
use SpiffyForm\Form\Builder,
    Zend\Form\Form,
    Zend\Stdlib\Parameters;

abstract class Definition
{
    /**
     * Build the definition.
     * 
     * @param SpiffyForm\Form\Builder $b
     */
    abstract public function build(Builder $b);
    
    /**
     * After the initial form is generated you can use post build to add 
     * additional form elements or set options.
     * 
     * @param SpiffyForm\Form\Builder $b
     */
    public function postBuild(Builder $b)
    {}
    
    /**
     * Additional validation for the form definition if required.
     * 
     * @param Zend\Stdlib\Parameters $params
     * @param Zend\Form\Form         $form
     */
    public function isValid(Parameters $params, Form $form)
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
