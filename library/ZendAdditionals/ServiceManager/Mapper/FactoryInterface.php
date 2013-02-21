<?php
namespace ZendAdditionals\ServiceManager\Mapper;

use Zend\ServiceManager\ServiceLocatorInterface;
use ZendAdditionals\Db\Mapper\AbstractMapper;

interface FactoryInterface extends \Zend\ServiceManager\FactoryInterface
{
    /**
     * Get a new mapper instance to create this mapper service
     *
     * @param ServiceLocatorInterface $serviceLocator
     *
     * @return AbstractMapper
     */
    public function createMapper(ServiceLocatorInterface $serviceLocator);

    /**
     * Get a new entity instance to set on this mapper service
     *
     * @param ServiceLocatorInterface $serviceLocator
     *
     * @return object
     */
    public function createEntity(ServiceLocatorInterface $serviceLocator);

    /**
     * Get the database adapter (usually from the service locator)
     *
     * @param ServiceLocatorInterface $serviceLocator
     *
     * @return \Zend\Db\Adapter\Adapter
     */
    public function getDatabaseAdapter(ServiceLocatorInterface $serviceLocator);

    /**
     * Get a new hydrator instance used for this mapper
     * preferrably a hydrator implementing the ObservableStrategyInterface
     *
     * @param ServiceLocatorInterface $serviceLocator
     *
     * @return \Zend\Stdlib\Hydrator\ClassMethods
     */
    public function createHydrator(ServiceLocatorInterface $serviceLocator);
}
