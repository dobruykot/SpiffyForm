<?php
namespace SpiffyForm;

use Doctrine\Common\Annotations\AnnotationRegistry,
    Zend\Config\Config;

class Module
{
    public function init()
    {
        $this->initAutoloader();
        $this->initAnnotations();
    }
    
    public function initAnnotations()
    {
        AnnotationRegistry::RegisterFile(__DIR__ . '/src/SpiffyForm/Annotation/Form.php');
    }
    
    public function initAutoloader()
    {
        require __DIR__ . '/autoload_register.php';
    }

    public function getConfig()
    {
        return new Config(include __DIR__ . '/configs/module.config.php');
    }

    public function getClassmap()
    {
        return include __DIR__ . '/classmap.php';
    }
}