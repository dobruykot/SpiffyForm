<?php

namespace SpiffyForm\Annotation;

use InvalidArgumentException,
    RuntimeException,
    SpiffyForm\Annotation\RegexAnnotation;

class DoctrineORM extends RegexAnnotation
{
    public $type         = 'string';
    public $targetEntity = null;
    
    protected $types = array(
        'Column'     => 'column',
        'JoinColumn' => 'joincolumn',
        'ManyToOne'  => 'manytoone',
    );
    
    public function initialize($string)
    {
        $regex = sprintf('/@[\w_]+\\\\(%s)(?:\(([\w=",\s]+)\))?/', implode('|', array_keys($this->types)));
        if (($data = $this->parseRegex($regex, $string))) {
            if ($data[1] == 'Column') {
                if (!($args = $this->jsonDecode($data[2]))) {
                    throw new RuntimeException('invalid mapping arguments');
                }
                
                $this->type = $args['type'];
            } else if ($data[1] == 'ManyToOne') {
                if (!($args = $this->jsonDecode($data[2]))) {
                    throw new RuntimeException('invalid mapping arguments');
                }
                
                $this->type         = $this->types[$data[1]];
                $this->targetEntity = $args['targetEntity']; 
            }
            return true;
        }
        
        return false;
    }
}