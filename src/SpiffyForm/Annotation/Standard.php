<?php

namespace SpiffyForm\Annotation;

class Standard extends RegexAnnotation
{
    public $type    = 'text';
    public $options = array();
    
    public function initialize($string)
    {
        $regex = '/@Element(?:\(([\w="{}:,\s]+)\))?/';
        if (preg_match($regex, $string, $matches)) {
            if (isset($matches[1])) {
                $data = parent::jsonDecode($matches[1]);
                
                if (isset($data['type'])) {
                    $this->type = $data['type'];
                }
                if (isset($data['options'])) {
                    $this->options = (array) $data['options'];
                }
            }
            return true;
        }
        return null;
    }
}