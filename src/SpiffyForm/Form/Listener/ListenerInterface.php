<?php

namespace SpiffyForm\Form\Listener;

use Zend\EventManager;

interface ListenerInterface
{
    public function guessElement(EventManager\Event $e);
    
    public function getOptions(EventManager\Event $e);
    
    public function getValue(EventManager\Event $e);
    
    public function setValue(EventManager\Event $e);
}