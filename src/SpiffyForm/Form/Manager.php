<?php
namespace SpiffyForm\Form;
use Doctrine\Common\Annotations\Reader,
    SpiffyForm\Form\Definition,
    SpiffyForm\Form\Property\Collection as PropertyCollection,
    Zend\EventManager\EventCollection,
    Zend\EventManager\EventManager,
    Zend\Form\Form,
    Zend\Stdlib\Parameters;

class Manager
{
    /**
     * flag: is the form built?
     * 
     * @param boolean
     */
    protected $isBuilt = false;

    /**
     * Doctrine reader.
     * 
     * @var Doctrine\Common\Annotations\Reader
     */
    protected $reader;
    
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
     * Array of property collections.
     * 
     * @var array
     */
    protected $properties = array();
    
    /**
     * Form builder builds a form from annotations.
     * 
     * @param Reader       $reader the Doctrine annotation reader used to read annotated properties.
     * @param Definition   $type   the form definition used to build the form.
     * @param array|object $data   default array data or object to bind the form to.
     */
    public function __construct(Reader $reader, Definition $definition = null, $data = null)
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
        
        $this->reader      = $reader;
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
        
        $this->bindData();
        
        return $valid;
    }
    
    public function add($name, $spec = null, array $options = array())
    {
        $this->elements[$name] = array(
            'spec'    => $spec,
            'options' => $options,
        );
        return $this;
    }
    
    public function build()
    {
        if ($this->isBuilt) {
            return $this;
        }
        
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

        foreach($this->elements as $name => $properties) {
            $element = null;
            $options = $properties['options'];
            
            // element guessing
            if ($collection = $this->getPropertyCollection($properties['spec'])) {
                if ($property = $collection->getProperty($name)) {
                    $element = $property->getElement();
                    $options = array_merge($options, $property->getOptions());
                }
            } else {
                $element = $properties['spec'];
            }
            
            if (null === $element) {
                echo 'fixme: 4';
                exit;
            }
            $this->form->addElement($element, $name, $options);
        }
        
        $this->isBuilt = true;
        return $this;
    }

    public function getData()
    {
        return $this->data;
    }
    
    public function getForm()
    {
        $this->build();
        return $this->form;
    }
    
    /**
     * Retrieve the event manager
     *
     * Lazy-loads an EventManager instance if none registered.
     * 
     * @return EventCollection
     */
    public function events()
    {
        if (!$this->events instanceof EventCollection) {
            $this->setEventManager(new EventManager(array(__CLASS__, get_class($this))));
            $this->setDefaultListeners();
        }
        return $this->events;
    }
    
    public function setEventManager(EventCollection $events)
    {
        $this->events = $events;
    }
    
    protected function bindData()
    {
        $values = $this->getForm()->getValues();
        
        if (is_array($this->getData())) {
            $this->data = $values;
            return;
        }
        
        foreach($this->getForm()->getElements() as $element) {
            if (isset($values[$element->getName()])) {
                $this->setObjectValue($element->getName(), $values[$element->getName()]);
            }
        }
    }
    
    /**
     * Sets an object value.
     * 
     * @param string      $name
     * @param mixed       $value
     */
    protected function setObjectValue($name, $value)
    {
        $data = $this->getData();
        $vars = get_object_vars($data);
        
        $setter = 'set' . ucfirst($name);
        if (method_exists($data, $setter)) {
            $data->$setter($value);
        } else if (isset($object->$name) || array_key_exists($name, $vars)) {
            $data->$name = $value;
        } else {
            throw new \RuntimeException(sprintf(
                '%s (%s) could not be bound to %s. Try implementing %s::%s().',
                $name,
                $value,
                get_class($data),
                get_class($data),
                $setter
            ));
        }
    }
    
    protected function setDefaultListeners()
    {
        $this->events()->attach('element.guess', array(new Listener\BaseGuessListener, 'load'));
        $this->events()->attach('element.options', array(new Listener\BaseOptionsListener, 'load'));
    }
    
    protected function getPropertyCollection($spec)
    {
        if (is_object($spec)) {
            if (!$spec instanceof Definition) {
                // todo: throw exception
            }
            // todo: return definition based collection
        }
        
        if (is_object($this->data)) {
            $dataClass = get_class($this->data);
            if (!isset($this->properties[$dataClass])) {
                $this->properties[$dataClass] = new PropertyCollection(
                    $this,
                    $this->reader,
                    $this->data
                );
            }
            return $this->properties[$dataClass];
        }
        
        return null;
    }
}
