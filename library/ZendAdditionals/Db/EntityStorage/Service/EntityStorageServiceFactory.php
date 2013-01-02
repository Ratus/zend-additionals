<?php

namespace ZendAdditionals\Db\EntityStorage\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class EntityStorageServiceFactory implements FactoryInterface
{
    /**
     * Creates a new EntityStorage service
     *
     * @param ServiceLocatorInterface $serviceLocator
     *
     * @return EntityStorage
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $service = new EntityStorage();
        $service->setServiceLocator($serviceLocator);
        return $service;
    }
}

