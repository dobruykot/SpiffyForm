<?php
namespace SpiffyForm\Form;
use Doctrine\Common\Annotations\Reader,
    Doctrine\ORM\EntityManager,
    Doctrine\ORM\Mapping;

class ManagerDoctrine extends Manager
{
    /**
     * @var Doctrine\ORM\EntityManager
     */
    protected $entityManager;
    
    /**
     * Form builder builds a form from annotations.
     * 
     * @param EntityManager  $em         doctrine entity manager
     * @param Reader         $reader     doctrine annotation reader
     * @param string|object  $type       the form definition or dataObject to use to build the form.
     * @param null|object    $dataObject the dataObject to set.
     * @throws InvalidArgumentException  if dataObject is empty.
     * @throws InvalidArgumentException  if dataObject is not a string or object.
     */
    public function __construct(EntityManager $em, Reader $reader, $object = null, $dataObject = null)
    {
        parent::__construct($reader, $object, $dataObject);
    }
    
    /**
     * Get the entity manager
     * 
     * @return Doctrine\ORM\EntityManager
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }
    
    /**
     * Set the entity manager
     * 
     * @param Doctrine\ORM\EntityManager $em
     */
    public function setEntityManager($em)
    {
        $this->entityManager = $em;
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
    {
        switch($element) {
            case 'entity':
                if (!isset($options['class'])) {
                    $mdata = $this->getEntityManager()->getClassMetadata(get_class($object));
                    if (!isset($mdata->associationMappings[$name])) {
                        throw new \RuntimeException(sprintf(
                            'field type for "%s" was entity but no class could be determined',
                            $name
                        ));
                    }
                    
                    $options['class'] = $mdata->associationMappings[$name]['targetEntity'];
                }
                
                if (!isset($options['entityManager'])) {
                    $options['entityManager'] = $this->getEntityManager();
                }
                break;
        }
        
        if (!$annotations) {
            return;
        }
        
        // @todo: check to see if filters/validators already exist before adding
        foreach($annotations as $a) {
            if ($a instanceof Mapping\Column) {
                if (!$a->nullable) {
                    isset($options['required']) ? $options['required'] : true;
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
            if ($a instanceof Element || $a instanceof Mapping\Column) {
                if (isset($this->defaultTypes[$a->type])) {
                    $type = $this->defaultTypes[$a->type];
                }
                break;
            } else if ($a instanceof Mapping\ManyToOne) {
                $type = 'entity';
            }
        }
        
        if (!$type) {
            return null;
        }
        return $type;
    }
}
