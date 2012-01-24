<?php

namespace SpiffyForm\Form\Builder;

use Doctrine\ORM\EntityManager,
    SpiffyForm\Form\Definition,
    SpiffyForm\Form\Builder\Standard,
    SpiffyForm\Form\Listener;

class DoctrineORM extends Standard
{
    protected $em;
    
    public function __construct(EntityManager $em, Definition $definition = null, $data = null)
    {
        $this->setEntityManager($em);
        
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

        $this->defaultAnnotations[] = 'SpiffyForm\Annotation\DoctrineORM';
        
        return $this;
    }
    
    protected function setDefaultListeners()
    {
        parent::setDefaultListeners();
        
        $this->events()->attachAggregate(new Listener\DoctrineORM);
    }
}