<?php

namespace SpiffyForm\Annotation;

use InvalidArgumentException,
    RuntimeException,
    SpiffyForm\Annotation\RegexAnnotation;

class DoctrineMongoODM extends RegexAnnotation
{
    public $type           = 'string';
    public $targetDocument = null;
    
    protected $types = array(
        'Id'           => 'id',
        'Bin'           => 'bin',
        'BinCustom'     => 'bin_custom',
        'BinFunc'       => 'bin_func',
        'BinMD5'        => 'bin_md5',
        'BinUUID'       => 'bin_uuid',
        'Boolean'       => 'boolean',
        'Collection'    => 'collection',
        'Date'          => 'date',
        'Field'         => 'field',
        'Float'         => 'float',
        'Hash'          => 'hash',
        'Int'           => 'int',
        'String'        => 'string',
        'ReferenceMany' => 'document',
        'ReferenceOne'  => 'document',
        'EmbedMany' => 'document',
        'EmbedOne'  => 'document'
    );
    
    public function initialize($string)
    {
        $regex = sprintf('/@[\w_]+\\\\(%s)(?:\(([\w="]+)\))?/', implode('|', array_keys($this->types)));
        if (($data = $this->parseRegex($regex, $string))) {
            if (isset($data[2])) {
                if (!($args = $this->jsonDecode($data[2]))) {
                    throw new RuntimeException('invalid mapping arguments');
                }
                
                if (!in_array($data[1], array_keys($this->types))) {
                    throw new InvalidArgumentException(sprintf(
                        'type "%s" is not a valid mapping type',
                        $data[1]
                    ));
                }
                $this->type = $this->types[$data[1]];
                
                foreach($args as $name => $value) {
                    if (property_exists($this, $name)) {
                        $this->$name = $value;
                    }
                }
            } else {
                if (!isset($this->types[$data[1]])) {
                    throw new InvalidArgumentException(sprintf(
                        'type "%s" is not a valid mapping type',
                        $data[1]
                    ));
                }
                $this->type = $this->types[$data[1]];
            }
            return true;
        }
        
        return false;
    }
}