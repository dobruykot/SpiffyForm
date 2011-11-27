<?php
namespace SpiffyForm\Form\Guess;
use Zend\EventManager\ResponseCollection;

class Guess
{
    const LOW      = 1;
    const MEDIUM   = 2;
    const HIGH     = 3;
    const ABSOLUTE = 4;
    
    protected $confidence = self::LOW;
    
    public function __construct($value, $confidence)
    {
        $this->confidence = $confidence;
        $this->value      = $value;
    }
    
    public function getConfidence()
    {
        return $this->confidence;
    }
    
    public function getValue()
    {
        return $this->value;
    }
    
    public static function getBestGuess(ResponseCollection $collection)
    {
        foreach($collection as $guesses) {
            foreach($guesses as $guess) {
                $array[] = $guess;
            }
        }
        
        usort($array, function($a, $b) {
            return $b->getConfidence() - $a->getConfidence();
        });
        return count($array) > 0 ? $array[0]->getValue() : null;
    }
}
