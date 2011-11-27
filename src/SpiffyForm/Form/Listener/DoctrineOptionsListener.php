<?php
namespace SpiffyForm\Form\Listener;
use Zend\EventManager\Event;

class DoctrineOptionsListener implements Listener
{
    public function load(Event $e)
    {
        $options      = array();
        $property     = $e->getParam('property');
        $annotations  = $property->getAnnotations();
        $manager      = $property->getManager();
        $name         = $property->getName();
        $em           = $manager->getEntityManager();
        $mdata        = $em->getClassMetadata(get_class($manager->getData()));

        if (isset($mdata->fieldMappings[$name])) {
            $fieldMapping = $mdata->fieldMappings[$name];
            if ($fieldMapping['nullable'] !== true) {
                $options['required'] = true;
            }
        }
        
        $element = $property->getElement();
        switch($element) {
            case 'entity':
                $class = null;
                if (isset($mdata->associationMappings[$name])) {
                    $class = $mdata->associationMappings[$name]['targetEntity'];
                }
                
                $options = array(
                    'class'         => $class,
                    'entityManager' => $em,
                );
                break;
        }
        
        return $options;
    }
}
