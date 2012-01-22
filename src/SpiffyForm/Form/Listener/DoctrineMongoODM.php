<?php

namespace SpiffyForm\Form\Listener;

use Doctrine\ORM\Mapping\ClassMetadata,
    SpiffyForm\Form\Guess\Guess,
    SpiffyForm\Form\Listener\ListenerInterface,
    SpiffyForm\Annotation,
    SpiffyForm\Form\Element\Document,
    Zend\EventManager\Event,
    Zend\EventManager\EventCollection,
    Zend\EventManager\ListenerAggregate;

class DoctrineMongoODM implements ListenerAggregate, ListenerInterface
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
            if ($annotation instanceof Annotation\DoctrineMongoODM) {
                switch($annotation->type) {
                    case 'bin':
                    case 'bin_custom':
                    case 'bin_func':
                    case 'bin_md5':
                    case 'bin_uuid':
                    case 'collection';
                    case 'hash':
                        // not supported
                        break;
                    //set the id type as hidden
                    case 'id':
                        $guesses[] = new Guess('hidden', Guess::HIGH);
                        break;
                    case 'boolean':
                        $guesses[] = new Guess('checkbox', Guess::HIGH);
                        break;
                    case 'date':
                    case 'float':
                    case 'int':
                        $guesses[] = new Guess('text', Guess::LOW);
                        break;
                    case 'string':
                        $guesses[] = new Guess('text', Guess::MEDIUM);
                        break;
                    case 'document':
                        $guesses[] = new Guess('document', Guess::HIGH);
                        break;
                }
            }
        }

        return $guesses;
    }

    public function getValue(Event $e)
    {
        $property = $e->getTarget();
        
        if ($property->getElement() == 'document' && null !== $property->value) {
            $options = $property->getOptions();
            $dm      = $property->getBuilder()->getDocumentManager();
            $mdata   = $dm->getClassMetadata($options['targetDocument']);
            
            $property->value = $mdata->getIdentifierValue($property->value);
        }
    }
    
    public function setValue(Event $e)
    {
        $property = $e->getTarget();

        if ($property->getElement() == 'document') {
            if ($property->value === Document::NULL_VALUE) {
                $property->value = null;
            } else {
                $options = $property->getOptions();
                $dm      = $property->getBuilder()->getDocumentManager();
                
                $property->value = $dm->getReference($options['targetDocument'], $property->value);
            }
        }
    }

    public function getOptions(Event $e)
    {
        $property = $e->getTarget();
        $dm       = $property->getBuilder()->getDocumentManager();
        $options  = array();

        $name = $property->getName();
        $data = $property->getBuilder()->getData();

        if (is_object($data)) {
            $mdata = $dm->getClassMetadata(get_class($data));
            if (isset($mdata->fieldMappings[$name])) {
                $map = $mdata->fieldMappings[$name];

                //when we have an id with auto increment the strategy is INCREMENT and the type is custom_id
                $isId = ($map['type'] == 'id' && $map['strategy'] == 'auto') || ($map['type'] == 'custom_id' &&
                    $map['strategy'] == 'INCREMENT');
                $optional = $map['nullable'] || $map['type'] == 'boolean' || $isId;
                $options['required'] = !$optional;

                if ($isId) {
                    $options['ignore'] = true;
                    $options['decorators'] = array('ViewHelper');
                    $options['order'] = -100;
                }

                if ($property->getElement() == 'document') {
                    $options['documentManager'] = $property->getBuilder()->getDocumentManager();
                    $options['targetDocument']  = $map['targetDocument'];
                }
            }
        }
        
        return $options;
    }
}
