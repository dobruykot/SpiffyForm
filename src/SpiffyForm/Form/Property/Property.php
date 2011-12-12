<?php
namespace SpiffyForm\Form\Property;
use ReflectionException,
    ReflectionProperty,
    SpiffyForm\Form\Definition,
    SpiffyForm\Form\Manager,
    SpiffyForm\Form\Guess\Guess,
    Zend\Form\Form;

class Property
{
    const VALUE_NO_INIT = '___NO_INIT___';
    
    protected $name;
    protected $spec;
	
    protected $manager;
    protected $defaultOptions;

    protected $annotations = null;
    protected $options     = null;    
    protected $element     = null;
    public    $value       = self::VALUE_NO_INIT;
    
    public function __construct($name, $spec, array $defaultOptions = array(), Manager $manager)
    {
        $this->name           = $name;
        $this->spec           = $spec;
        $this->defaultOptions = $defaultOptions;
        $this->manager        = $manager;
        
        if (is_string($spec)) {
            $this->element = $spec;
        }
    }
    
    public function build(Form $form)
    {
        if ($this->spec instanceof Definition) {
            $this->spec->build($this->manager);
            return;
        }

        $form->addElement(
            $this->spec ? $this->spec : $this->getElement(),
            $this->getName(),
            $this->getOptions()
        );
        
        return $this;
    }
    
    public function getAnnotations()
    {
        if (null === $this->annotations) {
            $annotations = array();
            
            if ($reflProperty = $this->getReflectionProperty()) {
                $docComment = $this->getReflectionProperty()->getDocComment();
                foreach($this->manager->getDefaultAnnotations() as $annotation) {
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
            $response = $this->manager->events()->trigger(
                'guess.element',
                null,
                array('manager' => $this->manager, 'property' => $this)
            );
            
            $this->element = Guess::getBestGuess($response);
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
            $response = $this->manager->events()->trigger(
                'get.options',
                null,
                array('manager' => $this->manager, 'property' => $this)
            );
            
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
            $response = $this->manager->events()->trigger(
                'get.value',
                null,
                array('manager' => $this->manager, 'property' => $this)
            );
        }
        
        return $this->value;
    }
    
    public function setValue($value)
    {
        // set value so listeners have access
        $this->value = $value;
        
        $response = $this->manager->events()->trigger(
            'set.value',
            null,
            array('manager' => $this->manager, 'property' => $this)
        );
        
        // $this->value now contains listener modified value
        
        $data = $this->manager->getData();
        
        if (is_array($data)) {
            $data[$this->name] = $this->value;
            $this->manager->setData($data);
            return;
        }
        
        $setter = 'set' . ucfirst($this->name);
        
        if (method_exists($data, $setter)) {
            $data->$setter($this->value);
        } else if (($vars = get_object_vars($data)) && array_key_exists($name, $vars)) {
            $data->$name = $this->value;
        } else if ($reflProp = $this->getReflectionProperty()) {
            $this->getReflectionProperty()->setValue($data, $$this->value);
        } else {
            $this->value = null;
        }
        
        return $this;
    }
    
    public function getReflectionProperty()
    {
        if (!($reflClass = $this->manager->getReflectionClass())) {
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