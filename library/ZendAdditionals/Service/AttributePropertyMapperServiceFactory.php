<?php

namespace ZendAdditionals\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZendAdditionals\Db\Mapper\AttributePropertyMapper;


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
     * @return CustomMapper
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $adapter = $serviceLocator->get('Zend\Db\Adapter\Adapter');
        $mapper = new AttributePropertyMapper();
        $mapper->setDbAdapter($adapter);
        $mapper->setEntityPrototype(new \ZendAdditionals\Db\Entity\AttributeProperty);
        $mapper->setHydrator(new \ZendAdditionals\Stdlib\Hydrator\ObservableClassMethods);
        return $mapper;
    }
}
