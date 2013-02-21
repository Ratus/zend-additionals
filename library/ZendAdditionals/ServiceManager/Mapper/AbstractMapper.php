<?php
namespace ZendAdditionals\ServiceManager\Mapper;

use Zend\ServiceManager\ServiceLocatorInterface;
use ZendAdditionals\Db\Mapper;

abstract class AbstractMapper implements FactoryInterface
{


    /**
     * Creates the Profile mapper service
     *
     * @param ServiceLocatorInterface $serviceLocator
     *
     * @return Mapper\AbstractMapper
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $adapter = $this->getDatabaseAdapter($serviceLocator);
        $mapper = $this->createMapper($serviceLocator);
        $mapper->setDbAdapter($adapter);
        $mapper->setEntityPrototype($this->createEntity($serviceLocator));
        $hydrator = $this->createHydrator($serviceLocator);
        $hydrator->setServiceManager($serviceLocator);
        $mapper->setHydrator($hydrator);
        return $mapper;
    }
}
