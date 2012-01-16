<?php

namespace SpiffyForm\Form\Builder;

use SpiffyForm\Form\Builder,
    SpiffyForm\Form\Listener;

class Standard Extends Builder
{
    protected function setDefaultAnnotations()
    {
        $this->defaultAnnotations[] = 'SpiffyForm\Annotation\Standard';
        
        return $this;
    }
    
    protected function setDefaultListeners()
    {
        $this->events()->attachAggregate(new Listener\Standard);
    }
}