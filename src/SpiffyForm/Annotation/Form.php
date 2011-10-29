<?php
namespace SpiffyForm\Annotation;
use Doctrine\Common\Annotations\Annotation;

/** Zend Validator SuperClass */
class Form extends Annotation
{
}

/** @Annotation */
final class Element extends Form
{
    public $name = null;
    
    public $options = null;
    
    public $type = 'text';
}
