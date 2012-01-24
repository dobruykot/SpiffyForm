<?php

namespace SpiffyForm\Form\Builder;

use Doctrine\ODM\MongoDB\DocumentManager,
    SpiffyForm\Form\Definition,
    SpiffyForm\Form\Builder\Standard,
    SpiffyForm\Form\Listener;

class DoctrineMongoODM extends Standard
{
    protected $dm;
    
    public function __construct(DocumentManager $dm, Definition $definition = null, $data = null)
    {
        $this->setDocumentManager($dm);
        
        parent::__construct($definition, $data);
    }
    
    public function getDocumentManager()
    {
        return $this->documentManager;
    }
    
    public function setDocumentManager($dm)
    {
        $this->documentManager = $dm;
        return $this;
    }
    
    protected function setDefaultAnnotations()
    {
        parent::setDefaultAnnotations();

        $this->defaultAnnotations[] = 'SpiffyForm\Annotation\DoctrineMongoODM';
        
        return $this;
    }
    
    protected function setDefaultListeners()
    {
        parent::setDefaultListeners();
        
        $this->events()->attachAggregate(new Listener\DoctrineMongoODM);
    }
}