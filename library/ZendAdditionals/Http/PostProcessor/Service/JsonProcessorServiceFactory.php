<?php

namespace ZendAdditionals\Http\PostProcessor\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZendAdditionals\Http\PostProcessor\Json;

class JsonProcessorServiceFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return new Json;
    }
}

