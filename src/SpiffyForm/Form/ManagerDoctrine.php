<?php
namespace SpiffyForm\Form;
use Doctrine\Common\Annotations\Reader,
    Doctrine\ORM\EntityManager,
    Doctrine\ORM\Mapping,
    SpiffyForm\Form\Element\Entity;

class ManagerDoctrine extends Manager
{
    /**
     * @var Doctrine\ORM\EntityManager
     */
    protected $entityManager;
    
    /**
     * Form builder builds a form from annotations.
     * 
     * @param Reader       $reader the Doctrine annotation reader used to read annotated properties.
     * @param Definition   $type   the form definition used to build the form.
     * @param array|object $data   default array data or object to bind the form to.
     */
    public function __construct(EntityManager $em, Reader $reader, Definition $definition = null, $data = null)
    {
        $this->entityManager = $em;
        parent::__construct($reader, $definition, $data);
    }
    
    public function getEntityManager()
    {
        return $this->entityManager;
    }
    
    protected function setDefaultListeners()
    {
        parent::setDefaultListeners();
        
        $this->events()->attach('element.guess', array(new Listener\DoctrineGuessListener, 'load'));
        $this->events()->attach('element.options', array(new Listener\DoctrineOptionsListener, 'load'));
    }
    
    protected function bindData()
    {
        $values = $this->getForm()->getValues();
        
        if (is_array($this->getData())) {
            $this->data = $values;
            return;
        }
        
        $mdata = $this->getEntityManager()->getClassMetadata(get_class($this->getData()));
        foreach($this->getForm()->getElements() as $element) {
            if (isset($values[$element->getName()])) {
                $value = $values[$element->getName()];
                
                if ($element instanceof Entity && isset($mdata->associationMappings[$element->getName()])) {
                    $targetEntity = $mdata->associationMappings[$element->getName()]['targetEntity'];
                    $value = $this->getEntityManager()->getReference($targetEntity, $value);
                }
                
                $this->setObjectValue($element->getName(), $value);
            }
        }
    }
}
