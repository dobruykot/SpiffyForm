<?php
namespace SpiffyForm\Form\Property;
use SpiffyForm\Form\Guess\Element as ElementGuess,
    SpiffyForm\Form\Guess\Guess,
    SpiffyForm\Form\Manager;

class Property
{
    protected $annotations;
    protected $name;
    protected $element;
    protected $manager;
    protected $filters;
    protected $options;
    protected $validators;
    
    public function __construct($name, Manager $manager, array $annotations)
    {
        $this->name        = $name;
        $this->manager     = $manager;
        $this->annotations = $annotations;
    }
    
    public function getAnnotations()
    {
        return $this->annotations;
    }
    
    public function getFilters()
    {
        return $this->filters;
    }
    
    public function getElement()
    {
        if (null === $this->element) {
            $response = $this->manager->events()->trigger('element.guess', null, array('property' => $this));
            $this->element = Guess::getBestGuess($response);
        }
        return $this->element;
    }
    
    public function getName()
    {
        return $this->name;
    }
    
    public function getManager()
    {
        return $this->manager;
    }
    
    public function getOptions()
    {
        if (null === $this->options) {
            $response = $this->manager->events()->trigger('element.options', null, array('property' => $this));
            $options  = array();
            
            foreach($response as $option) {
                $options = array_merge($options, $option);
            }
            
            $this->options = $options;
        }
        return $this->options;
    }
    
    public function getValidators()
    {
        return $this->validators;
    }
}