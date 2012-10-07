<?php
namespace ZendAdditionals\Cache\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\Exception;

use Zend\Cache\StorageFactory;
use Zend\Cache\PatternFactory;

use ZendAdditionals\Cache\Pattern\LockingCache;
use ZendAdditionals\Cache\Pattern\LockingPatternOptions;

class LockingCachePatternServiceFactory implements FactoryInterface
{
    /**
     * Creates the Locking Cache service
     *
     * @param ServiceLocatorInterface $serviceLocator
     *
     * @return LockingCache
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Config');

        if (!isset($config['zend_additionals']['locking_cache'])) {
            throw new Exception\ServiceNotCreatedException(
                'Failed loading config: zend_additionals.locking_cache'
            );
        }

        if (!isset($config['zend_additionals']['locking_cache']['storage_factory'])) {
            throw new Exception\ServiceNotCreatedException(
                'Failed loading config: zend_additionals.locking_cache.storage_factory'
            );
        }

        if (!isset($config['zend_additionals']['locking_cache']['pattern'])) {
            throw new Exception\ServiceNotCreatedException(
                'Failed loading config: zend_additionals.locking_cache.global'
            );
        }

        $pluginManager = PatternFactory::getPluginManager();
        $pluginManager->setInvokableClass(
            'locking',
            'ZendAdditionals\Cache\Pattern\LockingCache'
        );

        $options = $config['zend_additionals']['locking_cache']['pattern'];
        $options['storage'] = StorageFactory::factory(
            $config['zend_additionals']['locking_cache']['storage_factory']
        );

        $patternOptions = new LockingPatternOptions($options);

        return PatternFactory::factory('locking', $patternOptions);
    }
}

