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
class AttributePropertyMapperServiceFactory implements FactoryInterface
{
    /**
     * Creates the CustomMapper service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return AttributeData
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $adapter = $serviceLocator->get('Zend\Db\Adapter\Adapter');
        $mapper = new Mapper\AttributeProperty();
        $mapper->setDbAdapter($adapter);
        $mapper->setEntityPrototype(new Entity\AttributeProperty);
        $hydrator = new ObservableClassMethods();
        $hydrator->setServiceManager($serviceLocator);
        $mapper->setHydrator($hydrator);
        return $mapper;
    }
}

