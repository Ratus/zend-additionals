<?php
namespace ZendAdditionals\AssetManager\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZendAdditionals\AssetManager\Resolver\CollectionResolver;

class CollectionResolverServiceFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     *
     * @return CollectionResolver
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config      = $serviceLocator->get('Config');
        $collections = array();

        if (isset($config['asset_manager']['resolver_configs']['collections'])) {
            $collections = $config['asset_manager']['resolver_configs']['collections'];
        }

        $collectionResolver = new CollectionResolver($collections);

        return $collectionResolver;
    }
}
