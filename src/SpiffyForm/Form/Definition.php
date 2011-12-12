<?php
namespace SpiffyForm\Form;
use SpiffyForm\Form\Manager,
    Zend\Form\Form,
    Zend\Stdlib\Parameters;

interface Definition
{
    /**
     * Every form must have a unique name.
     * 
     * @return string
     */
    public function getName();
    
    /**
     * Build the definition.
     * 
     * @param SpiffyForm\Form\Manager $m
     */
    public function build(Manager $m);
    
    /**
     * Additional validation for the form definition if required.
     * 
     * @param Zend\Stdlib\Parameters $params
     * @param Zend\Form\Form         $form
     */
    public function isValid($params, $form);
    
    /**
     * Get options for the definition.
     * 
     * @return array
     */
    public function getOptions();
}
