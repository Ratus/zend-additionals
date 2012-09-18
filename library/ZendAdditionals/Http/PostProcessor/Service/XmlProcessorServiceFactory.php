<?php

namespace ZendAdditionals\Http\PostProcessor\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZendAdditionals\Http\PostProcessor\Xml;

class XmlProcessorServiceFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return new Xml;
    }
}

