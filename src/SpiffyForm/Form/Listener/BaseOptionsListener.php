<?php
namespace SpiffyForm\Form\Listener;
use Zend\EventManager\Event,
    Zend\Filter\Word\CamelCaseToSeparator;

class BaseOptionsListener implements Listener
{
    protected static $filter;
    
    public function load(Event $e)
    {
        $options  = array();
        $property = $e->getParam('property');
        $element  = $property->getElement();
        
        $options['label'] = ucfirst($this->getFilter()->filter($property->getName()));
        
        if ($element == 'submit') {
            $options['ignore'] = true;
        }
        
        return $options;
    }
    
    protected function getFilter()
    {
        if (null === self::$filter) {
            self::$filter = new CamelCaseToSeparator();
        }
        return self::$filter;
    }
}