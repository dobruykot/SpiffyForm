<?php
namespace SpiffyForm\Form\Listener;
use Zend\EventManager\Event;

interface Listener
{
    public function guessElement(Event $e);
    
    public function getOptions(Event $e);
    
    public function getValue(Event $e);
    
    public function setValue(Event $e);
}
