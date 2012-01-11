<?php
namespace SpiffyForm\Form\Listener;
use Doctrine\ORM\Mapping\ClassMetadata,
    SpiffyForm\Annotation\Doctrine\ORM,
    SpiffyForm\Form\Guess\Guess,
    SpiffyForm\Form\Element\Entity,
    Zend\EventManager\Event;

class DoctrineListener implements Listener
{
    public function guessElement(Event $e)
    {
        $guesses     = array();
        $annotations = $e->getParam('property')->getAnnotations();
        
        foreach($annotations as $annotation) {
            if ($annotation instanceof ORM\Column) {
                switch($annotation->type) {
                    case 'array':
                    case 'object':
                        ; // not supported
                        break;
                    case 'boolean':
                        $guesses[] = new Guess('checkbox', Guess::HIGH);
                        break;
                    case 'datetime':
                    case 'datetimetz':
                    case 'date':
                    case 'time':
                        $guesses[] = new Guess('text', Guess::LOW);
                        break;
                    case 'float':
                    case 'bigint':
                    case 'decimal':
                    case 'integer':
                    case 'smallint':
                        $guesses[] = new Guess('text', Guess::MEDIUM);
                        break;
                    case 'text':
                        $guesses[] = new Guess('textarea', Guess::MEDIUM);
                        break;
                    case 'string':
                        $guesses[] = new Guess('text', Guess::MEDIUM);
                        break;
                }
            } else if ($annotation instanceof ORM\ManyToOne) {
                $guesses[] = new Guess('entity', Guess::HIGH);
            } else if ($annotation instanceof ORM\OneToMany) {
                $guesses[] = new Guess('entity', Guess::HIGH);
            }
        }

        return $guesses;
    }

    public function getValue(Event $e)
    {
        $property = $e->getParam('property');
        $manager  = $e->getParam('manager');
        
        if ($property->getElement() == 'entity' && null !== $property->value) {
            $options = $property->getOptions();
            $em      = $manager->getEntityManager();
            $mdata   = $em->getClassMetadata($options['class']);
            
            if (count($mdata->getIdentifierFieldNames()) > 1) {
                echo 'getvalue: fixme';
                exit;
            } else {
                if (method_exists($property->value, '__load')) {
                    $property->value->__load();
                }
                
                $property->value = current($mdata->getIdentifierValues($property->value));
            }
        }
    }
    
    public function setValue(Event $e)
    {
        $property = $e->getParam('property');
        $manager  = $e->getParam('manager');
        
        if ($property->getElement() == 'entity') {
            if ($property->value === Entity::NULL_VALUE) {
                $property->value = null;
                return;
            }
            
            $options = $property->getOptions();
            $em      = $manager->getEntityManager();
            $mdata   = $em->getClassMetadata($options['class']);
            
            if (is_numeric($property->value)) {
                if (count($mdata->getIdentifierFieldNames()) > 1) {
                    $element  = $manager->getForm()->getElement($property->getName());
                    $entities = $element->getEntities();
                    
                    $property->value = $entities[$property->value];
                } else {
                    $property->value = $em->getReference($options['class'], $property->value);
                }
            }
        }
    }

    public function getOptions(Event $e)
    {
        $options      = array();
        $property     = $e->getParam('property');
        $defaults     = $property->getDefaultOptions();
        $manager      = $e->getParam('manager');
        $annotations  = $property->getAnnotations();
        $name         = $property->getName();
        $em           = $manager->getEntityManager();
        
        $data = $manager->getData();

        if (is_object($data)) {
            $mdata = $em->getClassMetadata(get_class($data));
            if (isset($mdata->fieldMappings[$name])) {
                $fieldMapping = $mdata->fieldMappings[$name];
                if (!$fieldMapping['nullable'] && $fieldMapping['type'] != 'boolean') {
                    $options['required'] = true;
                }
            }
        }
        
        $element = $property->getElement();
        switch($element) {
            case 'entity':
                $options = array();
                if (isset($mdata->associationMappings[$name])) {
                    $assoc = $mdata->associationMappings[$name];
                    
                    switch($assoc['type']) {
                        case 2:
                            break;
                        case ClassMetadata::ONE_TO_MANY:
                            $options['multiple'] = true;
                            break;
                    }
                    
                    $options['class'] = $assoc['targetEntity'];
                }
                
                $options['entityManager'] = $em;
                break;
        }
        
        return $options;
    }
}
