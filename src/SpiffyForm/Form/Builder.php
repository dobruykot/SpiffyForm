<?php
namespace SpiffyForm\Form;
use ReflectionClass,
    RuntimeException,
    SpiffyForm\Form\Definition,
    Zend\Cache\Storage\Adapter\AbstractAdapter as CacheAdapter,
    Zend\EventManager\EventCollection,
    Zend\EventManager\EventManager,
    Zend\Form\Form,
    Zend\Stdlib\Parameters;

abstract class Builder
{
    /**
     * Static array of reflection classes for data objects.
     * 
     * @param array
     */
    protected static $reflClasses = array();
    
    /**
     * flag: is the form built?
     * 
     * @param boolean
     */
    protected $isBuilt = false;
    
    /**
     * An array of default annotation classes to check data with.
     * 
     * @param array
     */
    protected $defaultAnnotations = null;
    
    /**
     * Data bound to form.
     * 
     * @var mixed
     */
    protected $data = null;
    
    /**
     * Form definition, if set.
     * 
     * @var object
     */
    protected $definition;
    
    /**
     * Spiffy Form.
     * 
     * @var SpiffyForm\Form\Form
     */
    protected $form;
    
    /**
     * Zend\Form options.
     * 
     * @var array
     */
    protected $options;
    
    /**
     * EventManager
     * 
     * @var Zend\EventManager\EventManager
     */
    protected $events;
    
    /**
     * @var Zend\Cache\Storage\Adapter\AbstractAdapter
     */
    protected $cache;
    
    /**
     * Array of form properties (elements).
     * 
     * @var array
     */
    protected $properties = array();
    
    /**
     * Form builder builds a form from annotations.
     * 
     * @param null|Definition|string $definition the form definition used to build the form.
     * @param null|array|object      $data       default array data or object to bind the form to.
     */
    public function __construct($definition = null, $data = null)
    {
        if ($definition) {
            $this->setDefinition($definition);
        }
        $this->setData($data);
    }
    
    /**
     * Binds the data object to the params and checks the form for validity.
     * 
     * @return boolean
     */
    public function isValid(Parameters $params)
    {
        $valid = $this->getForm()->isValid($params->toArray());
        
        if ($this->definition) {
            $valid &= $this->definition->isValid($params, $this->getForm());
        }
        
        $this->bindData();
        
        return (bool) $valid;
    }
    
    public function add($name, $spec = null, array $options = array())
    {
        $this->properties[$name] = new Property\Property(
            $name,
            $spec,
            $options,
            $this
        );
        return $this;
    }
    
    public function build()
    {
        if ($this->isBuilt) {
            return $this;
        }
        
        $this->isBuilt = true;
        
        // todo: make this definition another property of the form?
        if ($this->definition) {
            $this->definition->build($this);
        }
        
        return $this;
    }
    
    public function getCache()
    {
        return $this->cache;
    }
    
    public function setCache(CacheAdapter $cache)
    {
        $this->cache = $cache;
        return $this;
    }
    
    public function setDefinition($definition)
    {
        if (is_string($definition)) {
            if (!class_exists($definition)) {
                throw new RuntimeException(sprintf(
                    'definition class (%s) could not be found',
                    $definition
                ));
            }
            $definition = new $definition;
        }
        
        $options = $definition->getOptions();

        if (empty($this->data) && isset($options['data_class'])) {
            $this->setData($options['data_class']);
        }
        
        $this->definition  = $definition;
        $this->options     = $options;
        
        return $this;
    }
    
    public function getProperty($name)
    {
        if (!isset($this->properties[$name])) {
            return null;
        }
        return $this->properties[$name];
    }

    public function getData()
    {
        return $this->data;
    }
    
    public function setData($data)
    {
        if (null === $data) {
            $data = array();
        } else if (is_string($data)) {
            if (!class_exists($data)) {
                throw new RuntimeException(sprintf(
                    'data class (%s) could not be found',
                    $data
                ));
            }
            $data = new $data;
        }
        
        $this->data = $data;
        return $this;
    }
    
    public function getForm()
    {
        if (null === $this->form) {
        //$cacheKey = null;
        //if ($this->definition) {
        //    $cacheKey = preg_replace('/[^a-z0-9_\+\-]+/', '', strtolower(str_replace('\\', '-', get_class($this->definition))));
        //}
        
        //if ($cacheKey && ($cache = $this->getCache())) {
            //if (false === ($form = $this->cache->getItem($cacheKey))) {
                $this->build();
        
                // create form
                $form = new Form($this->options);
		        $form->addPrefixPath(
		            'SpiffyForm\Form\Element',
		            'SpiffyForm/Form/Element',
		            'element'
		        );
        
                foreach($this->properties as $property) {
                    $property->build($form);
                    $form->getElement($property->getName())->setValue($property->getValue());
                }
                
                if ($this->definition) {
                    $this->definition->postBuild($this, $form);
                }
                
                //$this->cache->setItem($cacheKey, $form);
            //}
        //}
        
            $this->form = $form;
        }
        return $this->form;
    }
    
    public function setEventManager(EventCollection $events)
    {
        $this->events = $events;
    }
    
    public function events()
    {
        if (!$this->events instanceof EventCollection) {
            $this->setEventManager(new EventManager(array(__CLASS__, get_class($this))));
            $this->setDefaultListeners();
        }
        return $this->events;
    }
    
    public function getDefaultAnnotations()
    {
        if (null === $this->defaultAnnotations) {
            $this->setDefaultAnnotations();
        }
        return $this->defaultAnnotations;
    }
    
    public function getReflectionClass()
    {
        if (!is_object($this->getData())) {
            return null;
        }
        
        $dataClass = get_class($this->getData());
        if (!isset(self::$reflClasses[$dataClass])) {
            self::$reflClasses[$dataClass] = new ReflectionClass($dataClass);
        }
        return self::$reflClasses[$dataClass];
    }
    
    protected function bindData()
    {
        $values = $this->getForm()->getValues();
        
        foreach($this->getForm()->getElements() as $name => $element) {
            if (!$element || !isset($values[$name])) {
                continue;
            }
            
            if (!($property = $this->getProperty($name))) {
                continue;
            }
            
            $opts = $property->getOptions();
            if (!isset($opts['bind']) || $opts['bind']) {
                $property->setValue($values[$name]);
            }
        }
    }
    
    abstract protected function setDefaultAnnotations();
    
    abstract protected function setDefaultListeners();
}
