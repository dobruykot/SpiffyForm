<?php
namespace SpiffyForm\Annotation\Doctrine\ORM;
use SpiffyForm\Annotation\JsonAnnotation;

class Column extends JsonAnnotation
{
    public $type              = 'string';
    public $name;
    public $length;
    public $precision         = 0;
    public $scale             = 0;
    public $unique            = false;
    public $nullable          = false;
    public $columnDefinition;
    
    public function initialize($string)
    {
        if (preg_match('/Column\((?P<data>[\w\s,"=]+)\)/', $string, $matches)) {
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
