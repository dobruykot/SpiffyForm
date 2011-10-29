<?php
namespace SpiffyForm\Form\Exception;

class AutomaticTypeFailed extends \BadMethodCallException
{
    public function __construct($name, $class)
    {
        parent::__construct(sprintf(
            'Adding element (%s) failed because an element type could not be determined. ' . 
            'Did you set a data_class (%s::getDefaultOptions)?',
            $name,
            $class
        ));
    }
}
