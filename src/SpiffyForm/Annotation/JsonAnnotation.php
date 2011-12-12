<?php
namespace SpiffyForm\Annotation;
use RuntimeException,
    Zend\Code\Annotation\Annotation;

class JsonAnnotation implements Annotation
{
    /**
     * Error handler for unknown property accessor.
     *
     * @param string $name
     */
    public function __get($name)
    {
        throw new \BadMethodCallException(
            sprintf("Unknown property '%s' on annotation '%s'.", $name, get_class($this))
        );
    }

    /**
     * Error handler for unknown property mutator.
     *
     * @param string $name
     * @param mixed  $value
     */
    public function __set($name, $value)
    {
        throw new \BadMethodCallException(
            sprintf("Unknown property '%s' on annotation '%s'.", $name, get_class($this))
        );
    }
    
    public function initialize($string)
    {
        if (($properties = json_decode($string)) === null) {
            throw new RuntimeException(
                'annotation could not be parsed as valid JSON'
            );
        }
        foreach($properties as $key => $value) {
            $this->$key = $value;
        }
    }
}
