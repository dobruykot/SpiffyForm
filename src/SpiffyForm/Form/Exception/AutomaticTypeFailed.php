<?php
namespace SpiffyForm\Form\Exception;

class AutomaticTypeFailed extends \BadMethodCallException
{
    public function __construct($name)
    {
        parent::__construct(sprintf(
            'Adding type (%s) failed because an element could not be matched.', 
            $name
        ));
    }
}
