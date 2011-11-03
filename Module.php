<?php
namespace SpiffyForm;

use Zend\Config\Config;

class Module
{
    public function init()
    {
        $this->initAutoloader();
    }
    
    public function initAutoloader()
    {
        require __DIR__ . '/autoload_register.php';
    }

    public function getConfig()
    {
        return include __DIR__ . '/configs/module.config.php';
    }

    public function getClassmap()
    {
        return include __DIR__ . '/classmap.php';
    }
}