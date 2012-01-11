<?php
namespace SpiffyForm\Form\Listener;
use SpiffyForm\Annotation\Form,
    SpiffyForm\Form\Guess\Guess,
    Zend\EventManager\Event,
    Zend\Filter\Word\CamelCaseToSeparator;

class BaseListener implements Listener
{
    protected static $filter;
        
    public function guessElement(Event $e)
    {
        $guess       = array();
        $property    = $e->getParam('property');
        $annotations = $property->getAnnotations();
        $name        = $property->getName();

        $guesses[] = new Guess('text', Guess::LOW);
                
        if ($name == 'submit') {
            $guesses[] = new Guess('submit', Guess::MEDIUM);
        }
        
        foreach($annotations as $annotation) {
            if ($annotation instanceof Form\Element) {
                $guesses[] = new Guess($annotation->type, Guess::HIGH);
            }
        }
        
        return $guesses;
    }
    
    public function getOptions(Event $e)
    {
        $options  = array();
        $property = $e->getParam('property');
        $manager  = $e->getParam('manager');
        $element  = $property->getElement();
        
        $options['label'] = ucfirst($this->getFilter()->filter($property->getName()));
        
        if ($element == 'submit') {
            $options['ignore'] = true;
        }
        
        return $options;
    }
    
    public function getValue(Event $e)
    {
        $property = $e->getParam('property');
        $data     = $e->getParam('manager')->getData();
        $name     = $property->getName();
        $getter   = 'get' . ucfirst($name);
        $value    = $property->value;
        
        if (is_array($data)) {
            $property->value = isset($data[$name]) ? $data[$name] : null;
            return;
        }
        
        if (method_exists($data, $getter)) {
            $property->value = $data->$getter();
        } else if (($vars = get_object_vars($data)) && array_key_exists($name, $vars)) {
            $property->value = $data->$name;
        } else if ($reflProp = $property->getReflectionProperty()) {
            $property->value = $reflProp->getValue($data);
        } else {
            $property->value = null;
        }
    }
    
    public function setValue(Event $e)
    {}
    
    protected function getFilter()
    {
        if (null === self::$filter) {
            self::$filter = new CamelCaseToSeparator();
        }
        return self::$filter;
    }
}
