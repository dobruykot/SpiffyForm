<?php
namespace SpiffyForm\Form\Listener;
use Zend\EventManager\Event;

interface Listener
{
    public function load(Event $e);
}
