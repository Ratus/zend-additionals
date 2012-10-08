<?php
namespace ZendAdditionals\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZendAdditionals\Stdlib\Hydrator\ObservableClassMethods;
use ZendAdditionals\Db\Mapper;
use ZendAdditionals\Db\Entity;

/**
 * Factory class for Locator
 *
 * @category   Locator
 * @package    Locator
 */
class AttributeMapperServiceFactory implements FactoryInterface
{
    /**
     * Creates the CustomMapper service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return Attribute
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $adapter = $serviceLocator->get('Zend\Db\Adapter\Adapter');
        $mapper = new Mapper\Attribute();
        $mapper->setDbAdapter($adapter);
        $mapper->setEntityPrototype(new Entity\Attribute);
        $hydrator = new ObservableClassMethods();
        $hydrator->setServiceManager($serviceLocator);
        $mapper->setHydrator($hydrator);
        return $mapper;
    }
}

