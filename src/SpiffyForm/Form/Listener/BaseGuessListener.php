<?php
namespace SpiffyForm\Form\Listener;
use SpiffyForm\Form\Guess\Guess,
    Zend\EventManager\Event;

class BaseGuessListener implements Listener
{
    public function load(Event $e)
    {
        $guess    = array();
        $property = $e->getParam('property');
        $name     = $property->getName();

        $guesses[] = new Guess('text', Guess::LOW);
                
        if ($name == 'submit') {
            $guesses[] = new Guess('submit', Guess::MEDIUM);
        }
        
        return $guesses;
    }
}
