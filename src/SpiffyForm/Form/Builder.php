<?php
namespace SpiffyForm\Form;
use ReflectionClass;

class Builder extends AbstractForm
{
    const ELEMENT_NAMESPACE = 'SpiffyForm\Annotation\Element';
    
    public function init()
    {
        $elements = $this->_getDataObjectElements();
        foreach($elements as $name => $element) {
            $this->add(
                $element->name ? $element->name : $name,
                $element->type,
                $element->options,
                array($element)
            );
        }
        $this->add('submit');
    }
    
    private function _getDataObjectElements()
    {
        $props = $this->getReader()->getProperties($this->getDataObject(), self::ELEMENT_NAMESPACE);
        foreach($props as $name => $annotations) {
            $props[$name] = current($annotations);
        }
        return $props;
    }
}
