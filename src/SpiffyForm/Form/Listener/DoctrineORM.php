<?php

namespace SpiffyForm\Form\Listener;

use Doctrine\ORM\Mapping\ClassMetadata,
    SpiffyForm\Form\Guess\Guess,
    SpiffyForm\Form\Element\Entity,
    SpiffyForm\Form\Listener\ListenerInterface,
    SpiffyForm\Annotation,
    Zend\EventManager\Event,
    Zend\EventManager\EventCollection,
    Zend\EventManager\ListenerAggregate;

class DoctrineORM implements ListenerAggregate, ListenerInterface
{
    public function attach(EventCollection $events)
    {
        $events->attach('guess.element', array($this, 'guessElement'));
        $events->attach('get.options', array($this, 'getOptions'));
        $events->attach('set.value', array($this, 'setValue'));
        $events->attach('get.value', array($this, 'getValue'));
    }

    public function detach(EventCollection $events)
    {
        
    }
    
    public function guessElement(Event $e)
    {
        $guesses     = array();
        $annotations = $e->getTarget()->getAnnotations();
        
        foreach($annotations as $annotation) {
            if ($annotation instanceof Annotation\DoctrineORM) {
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
                    case 'manytoone':
                        $guesses[] = new Guess('entity', Guess::MEDIUM);
                        break;
                }
            }
        }

        return $guesses;
    }

    public function getValue(Event $e)
    {
        $property = $e->getTarget();
        $builder  = $property->getBuilder();
        
        if ($property->getElement() == 'entity' && null !== $property->value) {
            $options = $property->getOptions();
            $em      = $builder->getEntityManager();
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
        $property = $e->getTarget();
        $builder  = $property->getBuilder();
        
        if ($property->getElement() == 'entity') {
            if ($property->value === Entity::NULL_VALUE) {
                $property->value = null;
                return;
            }
            
            $options = $property->getOptions();
            $em      = $builder->getEntityManager();
            $mdata   = $em->getClassMetadata($options['class']);
            
            if (is_numeric($property->value)) {
                if (count($mdata->getIdentifierFieldNames()) > 1) {
                    $element  = $builder->getForm()->getElement($property->getName());
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
        $property     = $e->getTarget();
        $defaults     = $property->getDefaultOptions();
        $builder      = $property->getBuilder();
        $annotations  = $property->getAnnotations();
        $name         = $property->getName();
        $em           = $builder->getEntityManager();
        
        $data = $builder->getData();

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
