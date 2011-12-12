<?php
namespace SpiffyForm\Annotation\Doctrine\ORM;
use SpiffyForm\Annotation\JsonAnnotation;

class ManyToOne extends JsonAnnotation
{
    public $targetEntity = 'string';
    
    public function initialize($string)
    {
        if (preg_match('/ManyToOne\((?P<data>[\w\s,"=]+)\)/', $string, $matches)) {
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
