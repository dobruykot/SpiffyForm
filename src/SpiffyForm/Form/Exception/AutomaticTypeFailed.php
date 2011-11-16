<?php
namespace SpiffyForm\Form\Exception;

class AutomaticTypeFailed extends \BadMethodCallException
{
    public function __construct($name)
    {
        parent::__construct(sprintf(
            'Adding element (%s) failed because an element type could not be guessed.', 
            $name
        ));
    }
}
