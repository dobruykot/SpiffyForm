<?php
namespace SpiffyForm\Form;
use Doctrine\ORM\Mapping\Column,
    SpiffyAnnotation\Form\Element,
    SpiffyAnnotation\Assert\Assert,
    SpiffyAnnotation\Validator\Validator,
    SpiffyAnnotation\Service\Reader,
    Zend\Filter\Word\CamelCaseToSeparator,
    Zend\Form\Form as ZendForm;

class Form extends ZendForm
{
    const FILTER    = 0;
    const VALIDATOR = 1;
    
    const TYPE_SUBMIT = 'submit';
    const TYPE_STRING = 'string';
    
    protected $_defaultElements = array(
    /*
        Type::TARRAY        => 'select',
        Type::BIGINT        => 'text',
        Type::BOOLEAN       => 'checkbox',
        Type::DATETIME      => 'text',
        Type::DATETIMETZ    => 'text',
        Type::DATE          => 'date',
        Type::TIME          => 'time',
        Type::DECIMAL       => 'text',
        Type::INTEGER       => 'text',
        Type::SMALLINT      => 'text',
        Type::STRING        => 'text',
        Type::TEXT          => 'textarea',
        Type::FLOAT         => 'float',
     */
        self::TYPE_STRING   => 'text',
        self::TYPE_SUBMIT   => 'submit',
    );
    
    /**
     * Object to use for binding data.
     * 
     * @var object
     */
    protected $_dataObject;
    
    /**
     * Spiffy Annotation Reader.
     * 
     * @var SpiffyAnnotation\Service\Reader
     */
    protected $_reader;
    
    /**
     * Constructor
     *
     * Registers form view helper as decorator
     *
     * @param mixed $options
     * @return void
     */
    public function __construct($options = null)
    {
        parent::__construct($options);
        
        $this->_setDefaultsFromDataObject();
    }
    
    /**
     * Register default options. Can be overwritten using the constructor 
     * $options parameter.
     * 
     * @return array
     */
    public function getDefaultOptions()
    {
        return array();
    }
    
    /**    
     * Get the form's data object.
     * 
     * @return object|null
     */
    public function getDataObject()
    {
        if (null === $this->_dataObject) {
            $this->setDataObject();
        }
        return $this->_dataObject;
    }
    
    /**
     * Set the data object to use. If null, uses data_class from getDefaultOptions().
     * 
     * @param string|object|null $object
     * 
     * @return void
     */
    public function setDataObject($object = null)
    {
        if (null === $object) {
            $opts = $this->getDefaultOptions();
            if (isset($opts['data_class'])) {
                $object = new $opts['data_class'];
            }
        } else if (is_string($object)) {
            $object = new $object;
        } else if (!is_object($object)) {
            throw new \InvalidArgumentException('Expected an object or string');
        }

        $this->_dataObject = $object;
        
        // set defaults if possible
        if (is_object($object)) {
            $this->_setDefaultsFromDataObject();
        }
    }
    
    /**
     * Sets the reader.
     * 
     * @param SpiffyAnnotation\Service\Reader $reader
     */
    public function setReader(Reader $reader)
    {
        $this->_reader = $reader;
    }
    
    /**
     * Get the Doctrine reader.
     * 
     * @return SpiffyAnnotation\Service\Reader
     */
    public function getReader()
    {
        if (null === $this->_reader) {
            $this->_reader = new Reader;
        }
        return $this->_reader;
    }
    
    /**
     * Very primitively sets form defaults from a data object. Order:
     *   - Use toArray() on the object if it exists
     *   - Iterate through object properties and set using getter (or isser, if boolean)
     *   - Iterate through object properties.
     */
    private function _setDefaultsFromDataObject()
    {
       if (!$this->_dataObject) {
           return;
       } 
       
       $object = $this->_dataObject;
       if (method_exists($object, 'toArray')) {
           return $this->setDefaults($object->toArray());
       }
       
       $objectVars = get_object_vars($object);
       $defaults = array();
       foreach($this->getElements() as $element) {
            if (!$element->getIgnore()) {
                $getter = 'get' . ucfirst($element->getName());
                if (method_exists($object, $getter)) {
                    $defaults[$element->getName()] = $object->$getter();
                } else {
                    if ($element instanceof \Zend\Form\Element\Checkbox) {
                        $isser = 'is' . ucfirst($element->getName());
                        if (method_exists($object, $isser)) {
                            $defaults[$element->getName()] = $object->$isser();
                            continue;
                        }
                    } 
                    
                    if (isset($objectVars[$element->getName()])) {
                        $defaults[$element->getName()] = $object->{$element->getName()};
                    }
                }
            }
       }
       $this->setDefaults($defaults);
    }
    
    /**
     * Returns an array of filters or validators from an annotations array.
     * 
     * @param array $annotations
     * 
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
}

