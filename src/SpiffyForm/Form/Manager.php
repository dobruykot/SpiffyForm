<?php
namespace SpiffyForm\Form;
use Doctrine\ORM\Mapping\Column,
    SpiffyAnnotation\Form\Element,
    SpiffyAnnotation\Filter\Filter,
    SpiffyAnnotation\Validator\Validator,
    SpiffyAnnotation\Service\Reader as ReaderService,
    SpiffyForm\Form\Definition,
    Zend\Filter\Word\CamelCaseToSeparator,
    Zend\Form\Form as ZendForm,
    Zend\Stdlib\Parameters;

class Manager
{
    const FILTER    = 0;
    const VALIDATOR = 1;
    
    const DOCTRINE_COLUMN_ANNOTATION = 'Doctrine\ORM\Mapping\Column';
    const FORM_ELEMENT_ANNOTATION    = 'SpiffyAnnotation\Form\Element';
    const FILTER_ANNOTATION          = 'SpiffyAnnotation\Filter\Filter';
    const VALIDATOR_ANNOTATION       = 'SpiffyAnnotation\Validator\Validator';
    
    protected $_defaultTypes = array(
        'integer' => 'text',
        'string'  => 'text',
        'submit'  => 'submit'
    );
    
    /**
     * Spiffy Annotation Reader.
     * 
     * @var SpiffyAnnotation\Service\Reader
     */
    protected $_readerService;
    
    /**
     * Elements read from annotations.
     * 
     * @var array
     */
    protected $_elements;

    /**
     * Form definition, if set.
     * 
     * @var object
     */
    protected $_definition;
    
    /**
     * Data object the form binds to.
     * 
     * @var object
     */
    protected $_dataObject;
    
    /**
     * Spiffy Form.
     * 
     * @var SpiffyForm\Form\Form
     */
    protected $_form;
    
    /**
     * Form builder builds a form from annotations.
     * 
     * @param string|object $type       The form type or object to use to build the form.
     * @param null|object   $dataObject The dataObject to set.
     * @throws InvalidArgumentException if dataObject is empty.
     * @throws InvalidArgumentException if dataObject is not a string or object.
     */
    public function __construct($object = null, $dataObject = null)
    {
        if (is_string($object)) {
            $object = new $object;
        }
        
        if (!is_object($object)) {
            throw new \InvalidArgumentException('form builder requires a string or object');
        }
        
        if ($object instanceof Definition) {
            $this->_definition = $object;
            
            if ($dataObject) {
                $this->setDataObject($dataObject);
            }
            
            $this->_validateDataObjectFromDefinition();
        } else {
            $this->setDataObject($object);
        }
        
        $this->_form = new ZendForm;
        $this->_readDataObjectElements();
    }
    
    /**
     * Adds an element to the form using the annotation data from Doctrine
     * to guess certain elements of the form. Validators and filters are also
     * automatically injected from the object annotations.
     * 
     * @param string $name
     * @param string $element
     * @param null|array $options
     * @return SpiffyForm\Form\Builder, provides fluid interface.
     */
    public function add($name, $element = null, $options = null)
    {
        $object = $this->getDataObject();
        $field = isset($options['field']) ? $options['field'] : $name;
        
        if (isset($this->_elements[$name])) {
            $annotations = $this->_elements[$name];
            $element = $element ? $element : $this->_guessElementType($annotations);
            
            $options['filters'] = $this->_getFilterValidator(self::FILTER, $annotations);
            $options['validators'] = $this->_getFilterValidator(self::VALIDATOR, $annotations);
            
            // additional options based on Doctrine annotations (if available)
            $this->_addDoctrineOptions($options, $annotations);
        }
        
        // automatically setup submit type for submit name
        if ($name == 'submit' && !$element) {
            $element = $this->_defaultTypes['submit'];
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
        
        $this->getForm()->addElement($element, $name, $options);
        return $this;
    }
    
    /**
     * Automatically builds the form using all the available annotation information.
     * 
     * @return SpiffyForm\Form\Builder, provides a fluid interface.
     */
    public function build()
    {
        if ($this->_definition) {
            $this->_definition->build($this);
        } else {
            foreach($this->_elements as $name => $element) {
                $this->add($name);
            }
            $this->add('submit');
        }
        
        // set the defaults
        $this->_setFormFromDataObject();
        
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
        
        $this->_setDataObjectFromForm();
        
        return $valid;
    }
    
    /**
     * Sets the data object values from filtered/validated form values..
     */
    protected function _setDataObjectFromForm()
    {
        $values = $this->getForm()->getValues();
        foreach($this->_elements as $name => $data) {
            if (isset($values[$name])) {
                $this->_setDataObjectValue($name, $values[$name]);
            }
        }
    }
    
    protected function _setFormFromDataObject()
    {
        foreach($this->getForm()->getElements() as $element) {
            if (array_key_exists($element->getName(), $this->_elements)) {
                $element->setValue($this->_getDataObjectValue($element->getName()));
            } 
        }
    }
    
    protected function _getDataObjectValue($name)
    {
        $object = $this->getDataObject();
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
    
    protected function _setDataObjectValue($name, $value)
    {
        $object = $this->getDataObject();
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


    /**
     * Gets the form.
     * 
     * @return SpiffyForm\Form
     */
    public function getForm()
    {
        return $this->_form;
    }
    
    /**
     * Set the annotation reader service.
     * 
     * @param SpiffyAnnotation\Service\Reader $readerService
     */
    public function setReaderService(ReaderService $readerService)
    {
        $this->_readerService = $readerService;
    }
    
    /**
     * Get the annotation reader service.
     * 
     * @return SpiffyAnnotation\Service\Reader
     */
    public function getReaderService()
    {
        if (null === $this->_readerService) {
            $this->_readerService = new ReaderService;
        }
        return $this->_readerService;
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
        
        $this->_dataObject = $dataObject;
    }
    
    /**
     * Get the data object.
     * 
     * @return object
     */
    public function getDataObject()
    {
        return $this->_dataObject;
    }
    
    /**
     * Gets an element type based on a Doctrine mapping type.
     * 
     * @param array $annotations
     * 
     * @return string|null 
     */
    private function _guessElementType(array $annotations)
    {
        $type = null;
        foreach($annotations as $a) {
            if ($a instanceof Element || $a instanceof Column) {
                if (!isset($this->_defaultTypes[$a->type])) {
                    throw new Exception\AutomaticTypeFailed($a->type);
                }
                $type = $this->_defaultTypes[$a->type];
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
    private function _getFilterValidator($type, array $annotations)
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
    protected function _validateDataObjectFromDefinition()
    {
        if ($this->getDataObject()) {
            return;
        }
        
        if ($this->_definition->getDataObject()) {
            $this->setDataObject($this->_definition->getDataObject());
            return;
        }
        
        $options = $this->_definition->getOptions();
        if (!isset($options['dataClass'])) {
            throw new \RuntimeException(sprintf(
                'No data class could be found for %s. ' . 
                'Did you set a dataClass in getOptions()?',
                get_class($this->_definition)
            ));
        }
        $this->setDataObject($options['dataClass']);
    }
    
    protected function _addDoctrineOptions(array &$options, $annotations)
    {
        foreach($annotations as $a) {
            if ($a instanceof Column) {
                if (!$a->nullable) {
                    $options['required'] = true;
                }
                
                switch($a->type) {
                    case 'string':
                        $options['filters'][] = 'StringTrim';
                        if ($a->length) {
                            $add = true;
                            foreach($options['validators'] as $v) {
                                if (strcasecmp($v['validator'], 'stringlength') == 0) {
                                    $add = false;
                                    break;
                                }
                            }
                            if ($add) {
                                $options['validators'][] = array(
                                    'validator' => 'StringLength',
                                    'max' => $a->length
                                );
                            }
                        }
                        break;
                }
                break;
            }
        }
    }

    /**
     * Gets elements from the data object. Elements can be set from the Form\Element
     * notation or can be read from Doctrine columns.
     * 
     * @return array
     */
    protected function _readDataObjectElements()
    {
        $elements = $this->getReaderService()->getProperties(
            $this->getDataObject(),
            array(
                self::DOCTRINE_COLUMN_ANNOTATION,
                self::FORM_ELEMENT_ANNOTATION,
                self::FILTER_ANNOTATION,
                self::VALIDATOR_ANNOTATION
            )
        );
        $this->_elements = $elements;
    }
}
