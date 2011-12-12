<?php
namespace SpiffyForm\Form;
use ReflectionClass,
    SpiffyForm\Form\Definition,
    Zend\EventManager\EventCollection,
    Zend\EventManager\EventManager,
    Zend\Form\Form,
    Zend\Stdlib\Parameters;

class Manager
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
    protected $data;
    
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
     * Array of form properties (elements).
     * 
     * @var array
     */
    protected $properties = array();
    
    /**
     * Form builder builds a form from annotations.
     * 
     * @param Definition        $type   the form definition used to build the form.
     * @param null|array|object $data   default array data or object to bind the form to.
     */
    public function __construct(Definition $definition = null, $data = null)
    {
        $options = null;
        if ($definition) {
            $options         = $definition->getOptions();
            $options['name'] = $definition->getName(); 
        }
        
        if (null === $data) {
            if ($options && isset($options['data_class'])) {
                $data = new $options['data_class'];
                unset($options['data_class']);
            } else {
                $data = array();
            }
        }
        
        $this->definition  = $definition;
        $this->data        = $data;
        $this->options     = $options;
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
        
        // todo: make this definition another property of the form?
        if ($this->definition) {
            $this->definition->build($this);
        }
        
        // create form
        $this->form = new Form($this->options);
        $this->form->addPrefixPath(
            'SpiffyForm\Form\Element',
            'SpiffyForm/Form/Element',
            'element'
        );

        foreach($this->properties as $property) {
            $property->build($this->form);
            $this->form->getElement($property->getName())->setValue($property->getValue());
        }
        
        $this->isBuilt = true;
        return $this;
    }
    
    public function getProperty($name)
    {
        return $this->properties[$name];
    }

    public function getData()
    {
        return $this->data;
    }
    
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }
    
	/**
	 * @return Zend\Form\Form
	 */
    public function getForm()
    {
        $this->build();
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
            
            $opts = $this->getProperty($name)->getOptions();
            if (!isset($opts['bind']) || $opts['bind']) {
                $this->getProperty($name)->setValue($values[$name]);
            }
        }
    }
    
    protected function setDefaultAnnotations()
    {}
    
    protected function setDefaultListeners()
    {
        $this->events()->attach('guess.element', array(new Listener\BaseListener, 'guessElement'));
        $this->events()->attach('get.options', array(new Listener\BaseListener, 'getOptions'));
        $this->events()->attach('set.value', array(new Listener\BaseListener, 'setValue'));
        $this->events()->attach('get.value', array(new Listener\BaseListener, 'getValue'));
    }
}
