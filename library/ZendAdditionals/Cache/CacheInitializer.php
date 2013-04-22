<?php
namespace ZendAdditionals\Cache;

use Zend\Cache\StorageFactory;
use Zend\Cache\PatternFactory;
use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\ServiceLocatorInterface;

use ZendAdditionals\Stdlib\ArrayUtils;

/**
 * @category    ZendAdditionals
 * @package     Cache
 */
class CacheInitializer
{
    const SERVICE_NS = 'rtscache\\';

    protected static $initialized = false;
    protected static $lockingCacheServiceKey;

    /**
     * Initializer for the rts cache
     *
     * @param ServiceManager $serviceManager
     */
    public static function init(ServiceManager $serviceManager)
    {
        if (self::$initialized) {
            return;
        }

        self::initializePatterns($serviceManager);
        self::registerAdapters($serviceManager);
        self::registerLockingCacheAware($serviceManager);

        self::$initialized = true;
    }

    /**
     * Register self defined patterns
     *
     * @param ServiceManager $serviceManager
     */
    protected static function initializePatterns(ServiceManager $serviceManager)
    {
        $invokables = ArrayUtils::arrayTarget(
            'rts_cache.patterns.invokables',
            $serviceManager->get('Config'),
            array()
        );

        foreach ($invokables as $alias => $class) {
            PatternFactory::getPluginManager()->setInvokableClass($alias, $class);
        }
    }

    /**
     * Register the adapters in the service manager
     *
     * @param ServiceManager $serviceManager
     * @throws Exception\InvalidConfigException
     */
    protected static function registerAdapters(ServiceManager $serviceManager)
    {
        $adapters = ArrayUtils::arrayTarget(
            'rts_cache.adapters',
            $serviceManager->get('Config'),
            array()
        );

        if (array_key_exists('default', $adapters) === false) {
            throw new Exception\InvalidConfigException(
                "Missing default rts_cache.adapter.default configuration"
            );
        }

        foreach ($adapters as $name => $settings) {
            $service  = self::SERVICE_NS . $name;

            // Validate the config
            self::validateCacheServiceConfig($settings, 'rts_cache.adapters.' . $name);

            // Create new adapter
            $adapter = $storage = StorageFactory::adapterFactory($settings['adapter']['name']);
            $storage->setOptions($settings['adapter']['options']);

            // Apply pattern when given
            if (array_key_exists('pattern', $settings)) {
                $settings['pattern']['options']['storage'] = $storage;

                $adapter = PatternFactory::factory(
                    $settings['pattern']['name'],
                    new $settings['pattern']['options_class']($settings['pattern']['options'])
                );

                if (
                    $settings['pattern']['name'] === 'locking' &&
                    self::$lockingCacheServiceKey === null
                ) {
                    self::$lockingCacheServiceKey = $service;
                }
            }

            // Set the new adapter in the service manager
            $serviceManager->setService($service, $adapter);
        }
    }

    /**
     * @param ServiceManager $serviceManager
     */
    protected static function registerLockingCacheAware(ServiceManager $serviceManager)
    {
        if (self::$lockingCacheServiceKey === null) {
            return;
        }

        $serviceManager->addInitializer(array(__CLASS__, 'lockingCacheAware'));
    }

    /**
     * Will be called by the servicemanager initializer
     *
     * @param mixed $instance
     * @param ServiceLocatorInterface $serviceLocator
     */
    public static function lockingCacheAware(
        $instance,
        ServiceLocatorInterface $serviceLocator
    ) {
        if ($instance instanceof LockingCacheAwareInterface) {
            $instance->setLockingCache(
                $serviceLocator->get(self::$lockingCacheServiceKey)
            );
        }
    }

    /**
     * Validates the user configuration and make sure everything is valid
     *
     * @param array $config
     * @param string $namespace
     * @return  void
     * @throws Exception\InvalidConfigException
     */
    protected static function validateCacheServiceConfig(array &$config, $namespace = null)
    {
        if (array_key_exists('adapter', $config) === false) {
            throw new Exception\InvalidConfigException(
                "Missing config key in config {$namespace}.adapter"
            );
        }

        $adapter = &$config['adapter'];

        if (array_key_exists('name', $adapter) === false) {
            throw new Exception\InvalidConfigException(
                "Missing config key in config {$namespace}.adapter.name"
            );
        }

        if (array_key_exists('options', $adapter) === false) {
            $adapter['options'] = array();
        }

        if (array_key_exists('pattern', $config) === false) {
            return;
        }

        $pattern = &$config['pattern'];
        if (array_key_exists('name', $pattern) === false) {
            throw new Exception\InvalidConfigException(
                "Missing config key in config {$namespace}.pattern.name"
            );
        }
        if (array_key_exists('options_class', $pattern) === false) {
            throw new Exception\InvalidConfigException(
                "Missing config key in config {$namespace}.pattern.options_class"
            );
        }

        if (class_exists($pattern['options_class']) === false) {
            throw new Exception\InvalidConfigException(
                "Class '{$pattern['options_class']} does not exists!"
            );
        }

        if (array_key_exists('options', $pattern) === false) {
            $pattern['options'] = array();
        }
    }
}
