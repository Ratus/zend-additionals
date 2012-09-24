<?php

namespace ZendAdditionals\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZendAdditionals\Db\Mapper\AttributeMapper;


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
     * @return CustomMapper
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $adapter = $serviceLocator->get('Zend\Db\Adapter\Adapter');
        $mapper = new AttributeMapper();
        $mapper->setDbAdapter($adapter);
        $mapper->setEntityPrototype(new \ZendAdditionals\Db\Entity\Attribute);
        $mapper->setHydrator(new \ZendAdditionals\Stdlib\Hydrator\ObservableClassMethods);
        return $mapper;
    }
}
