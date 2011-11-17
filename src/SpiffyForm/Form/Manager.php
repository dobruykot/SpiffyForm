<?php
namespace SpiffyForm\Form;
use Doctrine\Common\Annotations\Reader,
    Doctrine\ORM\EntityManager,
    ReflectionClass,
    SpiffyAnnotation\Filter\Filter,
    SpiffyAnnotation\Validator\Validator,
    SpiffyForm\Form\Definition,
    Zend\Filter\Word\CamelCaseToSeparator,
    Zend\Form\Form as ZendForm,
    Zend\Stdlib\Parameters;

class Manager
{
    const FILTER    = 0;
    const VALIDATOR = 1;
    
    /**
     * @var array
     */
    protected $defaultTypes = array(
        'integer'  => 'text',
        'string'   => 'text',
        'text'     => 'text',
        'boolean'  => 'checkbox',
        'checkbox' => 'checkbox',
        'submit'   => 'submit',
    );

    /**
     * Doctrine reader.
     * 
     * @var Doctrine\Common\Annotations\Reader
     */
    protected $reader;
    
    /**
     * Elements read from annotations.
     * 
     * @var array
     */
    protected $elements;

    /**
     * Form definition, if set.
     * 
     * @var object
     */
    protected $definition;
    
    /**
     * Data object the form binds to.
     * 
     * @var object
     */
    protected $dataObject;
    
    /**
     * An array of subform definitions.
     * 
     * @var array
     */
    protected $subforms = array();
    
    /**
     * Spiffy Form.
     * 
     * @var SpiffyForm\Form\Form
     */
    protected $form;
    
    /**
     * Form builder builds a form from annotations.
     * 
     * @param Reader         $reader     doctrine annotation reader
     * @param string|object  $type       the form definition or dataObject to use to build the form.
     * @param null|object    $dataObject the dataObject to set.
     * @throws InvalidArgumentException  if dataObject is empty.
     * @throws InvalidArgumentException  if dataObject is not a string or object.
     */
    public function __construct(Reader $reader, $object = null, $dataObject = null)
    {
        $this->reader = $reader;
        
        if (is_string($object)) {
            $object = new $object;
        }
        
        if (!is_object($object)) {
            throw new \InvalidArgumentException('form builder requires a string or object');
        }
        
        if ($object instanceof Definition) {
            $this->definition = $object;
            
            if (!$dataObject) {
                $dataObject = $this->getDataObjectFromDefinition($this->definition);
                $this->setDataObject($dataObject);
            }
        } else {
            $this->setDataObject($object);
        }
        
        // create form and register custom paths
        $this->form = new ZendForm;
        $this->form->addPrefixPath('SpiffyForm\Form\Element', 'SpiffyForm/Form/Element', 'element');
        
        $this->elements = $this->readDataObjectElements();
    }
    
    /**
     * Gets the id for a definition.
     * 
     * @param string|object $definition
     * @return string
     */
    public function getDefinitionId($definition)
    {
    	if (is_object($definition)) {
    		$definition = get_class($definition);
    	}
    	return strtolower(str_replace('\\', '_', $definition));
    }
    
    /**
     * Adds an element to the form using the annotation data to guess certain 
     * elements of the form. Validators and filters are also automatically injected 
     * from the object annotations.
     * 
     * @param string     $name
     * @param string     $element
     * @param null|array $options
     * @throws InvalidArgumentException if an object is provided and is not a form Definition.
     * @return SpiffyForm\Form\Builder, provides fluid interface.
     */
    public function add($name, $element = null, $options = null)
    {
        if (is_string($name) && class_exists($name)) {
			$name = new $name;        	
        } 
        
       	if (is_object($name)) {
       		if ($name instanceof Definition) {
            	$this->addSubFormDefinition($name);
            	return $this;
       		} else {
       			throw new \InvalidArgumentException('object must be an instance of Definition');
       		}
        }
        
        $object      = $this->getDataObject();
        $field       = isset($options['field']) ? $options['field'] : $name;
        $annotations = null;
        $subForm     = null;
        
        // find the element from the base elements or a subform if available
        if (isset($this->elements[$name])) {
            $annotations = $this->elements[$name];
        } else {
            foreach($this->subforms as $sf) {
                if (isset($sf['elements'][$name])) {
                    $object      = $sf['dataObject'];
                    $subForm     = $sf['form'];
                    $annotations = $sf['elements'][$name];
                    break;
                }
            }
        }
        
        if ($annotations) {
            $element = $element ? $element : $this->guessElementType($annotations);
            
            $options['filters']    = $this->getFilterValidator(self::FILTER, $annotations);
            $options['validators'] = $this->getFilterValidator(self::VALIDATOR, $annotations);
        }
        
        // automatically setup submit type for submit name
        if ($name == 'submit' && !$element) {
            $element = $this->defaultTypes['submit'];
            $options['ignore'] = true;
        }
        
        // automatically add label if one doesn't exist
        if (!$options || !array_key_exists('label', $options)) {
            $filter = new CamelCaseToSeparator();
            $options['label'] = ucfirst($filter->filter($name));
        }
        
        if (!$element) {
            throw new Exception\AutomaticTypeFailed($name, get_class($this));
        }
        
        // extending
        $this->addAdditionalOptions($name, $element, $options, $object, $annotations);
        
        if ($subForm) {
            $subForm->addElement($element, $name, $options);
        } else {
            $this->getForm()->addElement($element, $name, $options);
        }
        
        return $this;
    }

    /**
     * Automatically builds the form using all the available annotation information.
     * 
     * @return SpiffyForm\Form\Builder, provides a fluid interface.
     */
    public function build()
    {
        if ($this->definition) {
            $this->definition->build($this);
        } else {
            foreach($this->elements as $name => $element) {
                $this->add($name);
            }
            $this->add('submit');
        }
        
        // set the defaults
        $this->setFormDefaultsFromDataObject();
        
        return $this;
    }
    
    /**
     * Binds the data object to the params and checks the form for validity.
     * 
     * @return boolean
     */
    public function isValid(Parameters $params)
    {
        $valid = $this->getForm()->isValid($params->toArray());
        
        $this->setDataObjectFromForm();
        
        return $valid;
    }

    /**
     * Gets the form.
     * 
     * @return SpiffyForm\Form
     */
    public function getForm()
    {
        return $this->form;
    }
    
    /**
     * Set the data object.
     * 
     * @param string|object $dataObject
     * @throws InvalidArgumentException if data object is not a string or object.
     */
    public function setDataObject($dataObject)
    {
        if (is_string($dataObject)) {
            $dataObject = new $dataObject;
        }
        
        if (!is_object($dataObject)) {
            throw new \InvalidArgumentException('data object must be a string or object.');
        }
        
        $this->dataObject = $dataObject;
    }
    
    /**
     * Gets the data object assigned to a specific definition.
     * 
     * @param string|object $definition
     */
    public function getDefinitionDataObject($definition)
    {
    	if (is_object($definition)) {
    		$definition = get_class($definition);
    	}
    	if (isset($this->subforms[$this->getDefinitionId($definition)])) {
    		return $this->subforms[$this->getDefinitionId($definition)]['dataObject'];
    	}
    	return null;
    }
    
    /**
     * Get the data object.
     * 
     * @return object
     */
    public function getDataObject()
    {
        return $this->dataObject;
    }
    
    /**
     * Get the reader.
     * 
     * @return Doctrine\Common\Annotations\Reader
     */
    public function getReader()
    {
        return $this->reader;
    }
    
    /**
     * Lets extending classes add additional options.
     * 
     * @param string     $name
     * @param string     $element
     * @param array      $options
     * @param object     $object
     * @param array|null $annotations
     */
    protected function addAdditionalOptions($name, $element, array &$options, $object, $annotations)
    {}
    
    /**
     * Gets an element type based or a Doctrine mapping type.
     * 
     * @param array $annotations
     * 
     * @return string|null 
     */
    protected function guessElementType(array $annotations)
    {
        $type = null;
        foreach($annotations as $a) {
            if ($a instanceof Element) {
                if (isset($this->defaultTypes[$a->type])) {
                    $type = $this->defaultTypes[$a->type];
                }
                break;
            }
        }
        
        if (!$type) {
            return null;
        }
        return $type;
    }
    
    /**
     * Returns an array of filters or validators from an annotations array.
     * 
     * @param array $annotations
     * @return array $filters
     */
    protected function getFilterValidator($type, array $annotations)
    {
        $stuff = array();
        foreach($annotations as $a) {
            switch($type) {
                case self::FILTER:
                    if ($a instanceof Filter) {
                        $stuff[] = str_replace('Zend\Filter\\', '', $a->class);
                    }
                    break;
                case self::VALIDATOR:
                    if ($a instanceof Validator) {
                        $stuff[] = array(
                            'validator' => str_replace('Zend\Validator\\', '', $a->class),
                            'breakChainOnFailure' => $a->breakChain,
                            'options' => $a->options
                        );
                    }
                    break;
            }
        }
        
        return $stuff;
    }
    
    /**
     * Sets a data object from the form definition.
     * 
     * @throws RuntimeException if no data object can be set
     */
    protected function getDataObjectFromDefinition(Definition $def)
    {
        if ($def->getDataObject()) {
            return $def->getDataObject();
        }
        
        $options = $def->getOptions();
        if (!isset($options['dataClass'])) {
            throw new \RuntimeException(sprintf(
                'No data class could be found for %s. ' . 
                'Did you set a dataClass in getOptions()?',
                get_class($def)
            ));
        }
        return new $options['dataClass'];
    }
    
    /**
     * Gets elements from the data object. Elements can be set from the Form\Element
     * annotation or can be read from Doctrine columns.
     * 
     * @return array
     */
    protected function readDataObjectElements($dataObject = null)
    {
        if (null === $dataObject) {
            $dataObject = $this->getDataObject();
        }

        $elements    = array();
        $reflClass   = new ReflectionClass($dataObject);
        $properties  = $reflClass->getProperties();
        $reader      = $this->getReader();
        
        foreach($properties as $property) {
            $elements[$property->getName()] = $reader->getPropertyAnnotations($property);
        }
        
        return $elements;
    }
    
    
    /**
     * Adds a subform definition.
     * 
     * @param Definition $definition
     */
    protected function addSubFormDefinition(Definition $definition)
    {
        $dataObject = $this->getDataObjectFromDefinition($definition);
        $elements   = $this->readDataObjectElements($dataObject);
        $form       = new \Zend\Form\SubForm;
        $id         = $this->getDefinitionId($definition);
        
        $this->subforms[$id] = array(
            'form'       => $form,
            'dataObject' => $dataObject,
            'definition' => $definition,
            'elements'   => $elements
        );
        
        $definition->build($this);
        
        $this->getForm()->addSubform(
            $form,
            $id
        );
    }
    
    /**
     * Sets the data object values from filtered/validated form values..
     */
    protected function setDataObjectFromForm()
    {
        $values = $this->getForm()->getValues();
        foreach($this->elements as $name => $data) {
            if (isset($values[$name])) {
                $this->setDataObjectValue($name, $values[$name]);
            }
        }

        foreach($this->subforms as $sfName => $sf) {
            foreach($sf['elements'] as $name => $data) {
                if (isset($values[$sfName][$name])) {
                    $this->setDataObjectValue($name, $values[$sfName][$name], $sf['dataObject']);
                }
            }
        }
    }
    
    /**
     * Set form defaults from data object.
     */
    protected function setFormDefaultsFromDataObject()
    {
        foreach($this->getForm()->getElements() as $element) {
            if (array_key_exists($element->getName(), $this->elements)) {
                $element->setValue($this->getDataObjectValue($element->getName()));
            } 
        }
    }
    
    /**
     * Gets a data object value.
     * 
     * @param string      $name
     * @param object|null $dataObject
     * @throws \RuntimeException if value could not be read
     */
    protected function getDataObjectValue($name, $dataObject = null)
    {
        $object = $dataObject ? $dataObject : $this->getDataObject();
        $vars = get_object_vars($object);
        
        $getter = 'get' . ucfirst($name);
        if (method_exists($object, $getter)) {
            return $object->$getter();
        } else if (isset($object->$name) || array_key_exists($name, $vars)) {
            return $object->$name;
        } else {
            throw new \RuntimeException(sprintf(
                '%s could not be read to form. Try implementing %s::%s().',
                $name,
                get_class($object),
                $getter
            ));
        }
    }
    
    /**
     * Sets a data object value.
     * 
     * @param string      $name
     * @param mixed       $value
     * @param object|null $dataObject
     * @throws \RuntimeException if value could not be set
     */
    protected function setDataObjectValue($name, $value, $dataObject = null)
    {
        $object = $dataObject ? $dataObject : $this->getDataObject();
        $vars = get_object_vars($object);
        
        $setter = 'set' . ucfirst($name);
        if (method_exists($object, $setter)) {
            $object->$setter($value);
        } else if (isset($object->$name) || array_key_exists($name, $vars)) {
            $object->$name = $value;
        } else {
            throw new \RuntimeException(sprintf(
                '%s (%s) could not be bound to %s. Try implementing %s::%s().',
                $name,
                $value,
                get_class($object),
                get_class($object),
                $setter
            ));
        }
    }
}
