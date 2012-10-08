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
class AttributeDataMapperServiceFactory implements FactoryInterface
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
        $mapper = new Mapper\AttributeData();
        $mapper->setDbAdapter($adapter);
        $mapper->setEntityPrototype(new Entity\AttributeData);
        $hydrator = new ObservableClassMethods();
        $hydrator->setServiceManager($serviceLocator);
        $mapper->setHydrator($hydrator);
        return $mapper;
    }
}

