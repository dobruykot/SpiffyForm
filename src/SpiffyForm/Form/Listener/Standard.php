<?php

namespace SpiffyForm\Form\Listener;

use SpiffyForm\Form\Guess\Guess,
    SpiffyForm\Annotation\Standard as StandardAnnotation,
    Zend\EventManager,
    Zend\Filter\Word\CamelCaseToSeparator;

class Standard implements EventManager\ListenerAggregate, ListenerInterface 
{
    protected static $filter;

    public function attach(EventManager\EventCollection $events)
    {
        $events->attach('guess.element', array($this, 'guessElement'));
        $events->attach('get.options', array($this, 'getOptions'));
        $events->attach('set.value', array($this, 'setValue'));
        $events->attach('get.value', array($this, 'getValue'));
    }

    public function detach(EventManager\EventCollection $events)
    {
        
    }

    public function guessElement(EventManager\Event $e)
    {
        $guess       = array();
        $property    = $e->getTarget();
        $annotations = $property->getAnnotations();
        $name        = $property->getName();

        $guesses[] = new Guess('text', Guess::LOW);
                
        if ($name == 'submit') {
            $guesses[] = new Guess('submit', Guess::MEDIUM);
        }
        
        foreach($annotations as $annotation) {
            if ($annotation instanceof StandardAnnotation) {
                $guesses[] = new Guess($annotation->type, Guess::HIGH);
            }
        }
        
        return $guesses;
    }
    
    public function getOptions(EventManager\Event $e)
    {
        $options  = array();
        $property = $e->getTarget();
        $element  = $property->getElement();
        
        $options['label'] = ucfirst($this->getFilter()->filter($property->getName()));
        
        if ($element == 'submit') {
            $options['ignore'] = true;
        }
        
        $annotations = $property->getAnnotations();
        foreach($annotations as $annotation) {
            if ($annotation instanceof StandardAnnotation) {
                $options = array_merge($options, (array) $annotation->options);
            }
        }
        
        return $options;
    }
    
    public function getValue(EventManager\Event $e)
    {
        $property = $e->getTarget();
        $data     = $property->getBuilder()->getData();
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
    
    public function setValue(EventManager\Event $e)
    {}
    
    protected function getFilter()
    {
        if (null === self::$filter) {
            self::$filter = new CamelCaseToSeparator();
        }
        return self::$filter;
    }
}
