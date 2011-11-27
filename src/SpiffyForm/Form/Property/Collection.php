<?php
namespace SpiffyForm\Form\Property;
use Doctrine\Common\Annotations\Reader,
    ReflectionClass,
    SpiffyForm\Form\Manager;
    
class Collection
{
    protected $isLoaded;
    protected $data;
    protected $manager;
    protected $properties;
    protected $reader;
    protected $reflClass;
    
    public function __construct(Manager $manager, Reader $reader, $data)
    {
        if (!is_object($data)) {
            // todo: throw exception
            echo 'failed';
            exit;
        }
        $this->manager   = $manager;
        $this->reader    = $reader;
        $this->data      = $data;
        $this->reflClass = new ReflectionClass($data);
    }
    
    public function getProperty($name)
    {
        $this->load();
        
        if ($this->hasProperty($name)) {
            return $this->properties[$name];
        } else {
            $this->properties[$name] = new Property(
                $name,
                $this->manager,
                array()
            );
        }
        return $this->properties[$name];
    }
    
    public function getProperties()
    {
        $this->load();
        
        return $this->properties;
    }
    
    public function hasProperty($name)
    {
        $this->load();
        
        return isset($this->properties[$name]);
    }
    
    protected function load()
    {
        if ($this->isLoaded) {
            return;
        }
        
        $properties = $this->reflClass->getProperties();
        $reader     = $this->reader;
        
        foreach($properties as $property) {
            $this->properties[$property->getName()] = new Property(
                $property->getName(),
                $this->manager,
                $reader->getPropertyAnnotations($property)
            );
        }
        $this->isLoaded = true;
    }
}
