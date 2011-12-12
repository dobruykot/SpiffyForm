<?php
namespace SpiffyForm\Form;
use Doctrine\ORM\EntityManager,
    Doctrine\ORM\Mapping,
    SpiffyForm\Annotation\Doctrine\ORM\Column,
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
     * @param Definition   $type   the form definition used to build the form.
     * @param array|object $data   default array data or object to bind the form to.
     */
    public function __construct(EntityManager $em, Definition $definition = null, $data = null)
    {
        $this->entityManager = $em;
        parent::__construct($definition, $data);
    }
    
    public function getEntityManager()
    {
        return $this->entityManager;
    }
    
    public function setEntityManager($em)
    {
        $this->entityManager = $em;
        return $this;
    }
    
    protected function setDefaultAnnotations()
    {
        parent::setDefaultAnnotations();

        $this->defaultAnnotations[] = 'SpiffyForm\Annotation\Doctrine\ORM\Column';
        $this->defaultAnnotations[] = 'SpiffyForm\Annotation\Doctrine\ORM\ManyToOne';
        
        return $this;
    }
    
    protected function setDefaultListeners()
    {
        parent::setDefaultListeners();
        
        $this->events()->attach('guess.element', array(new Listener\DoctrineListener, 'guessElement'));
        $this->events()->attach('get.options', array(new Listener\DoctrineListener, 'getOptions'));
        $this->events()->attach('set.value', array(new Listener\DoctrineListener, 'setValue'));
        $this->events()->attach('get.value', array(new Listener\DoctrineListener, 'getValue'));
        
        return $this;
    }
}
