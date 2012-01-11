<?php

namespace SpiffyForm\Annotation\Form;

use SpiffyForm\Annotation\JsonAnnotation;

class Element extends JsonAnnotation
{
    public $type      = 'string';
    public $name;
    public $length;
    public $precision = 0;
    public $scale     = 0;
    public $required  = false;
    
    public function initialize($string)
    {
        if (preg_match('/Element\((?P<data>[\w\s,"=]+)\)/', $string, $matches)) {
            $data = $matches['data'];
            $data = preg_replace('/\s*(\w+)=/', '"$1"=', $data);
            $data = str_replace('"=', '":', $data);
            $data = "{{$data}}";
            
            parent::initialize($data);
            
            return true;
        }
        
        return false;
    }
}
