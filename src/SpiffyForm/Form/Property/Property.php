<?php

namespace SpiffyForm\Form\Property;

use ReflectionException,
    ReflectionProperty,
    SpiffyForm\Form,
    Zend\Form\Form as ZendForm;

class Property
{
    const VALUE_NO_INIT = '___NO_INIT___';
    
    protected $name;
    protected $spec;
	
    protected $builder;
    protected $defaultOptions;

    protected $annotations = null;
    protected $options     = null;    
    protected $element     = null;
    public    $value       = self::VALUE_NO_INIT;
    
    public function __construct($name, $spec, array $defaultOptions = array(), Form\Builder $builder)
    {
        $this->name           = $name;
        $this->spec           = $spec;
        $this->defaultOptions = $defaultOptions;
        $this->builder        = $builder;
        
        if (is_string($spec)) {
            $this->element = $spec;
        }
    }
    
    public function getBuilder()
    {
        return $this->builder;
    }
    
    public function build(ZendForm $form)
    {
        if ($this->spec instanceof Definition) {
            $this->spec->build($this->builder);
            return;
        }

        $form->addElement(
            $this->spec ? $this->spec : $this->getElement(),
            $this->getName(),
            $this->getOptions()
        );
        
        return $this;
    }
    
    public function getAnnotations($name = null)
    {
        if (null === $this->annotations) {
            $annotations = array();
            
            if ($reflProperty = $this->getReflectionProperty()) {
                $docComment = $this->getReflectionProperty()->getDocComment();
                foreach($this->builder->getDefaultAnnotations() as $annotation) {
                    $obj = new $annotation;
                    if ($obj->initialize($docComment)) {
                        $annotations[] = $obj;
                    }
                }
            }
            
            $this->annotations = $annotations;
        }
        return $this->annotations;
    }
    
    public function getName()
    {
        return $this->name;
    }
    
    public function getElement()
    {
        if (null === $this->element) {
            $response = $this->builder->events()->trigger('guess.element', $this);
            
            $this->element = Form\Guess\Guess::getBestGuess($response);
        }
        
        return $this->element;
    }
    
    public function getDefaultOptions()
    {
        return $this->defaultOptions;
    }
    
    public function getOptions()
    {
        if (null === $this->options) {
            $options  = array();
            $response = $this->builder->events()->trigger('get.options', $this);
            
            foreach($response as $option) {
                $options = array_merge($option, $options);
            }
            $this->options = array_merge($options, $this->defaultOptions);
        }
        
        return $this->options;
    }
    
    public function getValue()
    {
        if ($this->value === self::VALUE_NO_INIT) {
            $response = $this->builder->events()->trigger('get.value', $this);
        }
        
        return $this->value;
    }
    
    public function setValue($value)
    {
        // set value so listeners have access
        $this->value = $value;
        
        $response = $this->builder->events()->trigger('set.value', $this);
        
        // $this->value now contains listener modified value
        
        $data = $this->builder->getData();
        
        if (is_array($data)) {
            $data[$this->name] = $this->value;
            $this->builder->setData($data);
            return;
        }
        
        $setter = 'set' . ucfirst($this->name);
        
        if (method_exists($data, $setter)) {
            $data->$setter($this->value);
        } else if (($vars = get_object_vars($data)) && array_key_exists($name, $vars)) {
            $data->$name = $this->value;
        } else if ($reflProp = $this->getReflectionProperty()) {
            $this->getReflectionProperty()->setValue($data, $this->value);
        } else {
            $this->value = null;
        }
        
        return $this;
    }
    
    public function getReflectionProperty()
    {
        if (!($reflClass = $this->builder->getReflectionClass())) {
            return null;
        }
        
        if (!$reflClass->hasProperty($this->getName())) {
            return null;
        }
        $reflProp = $reflClass->getProperty($this->getName());
        $reflProp->setAccessible(true);
        
        return $reflProp;
    }
}