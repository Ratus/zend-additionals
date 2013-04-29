<?php
namespace ZendAdditionals\Session;

use Zend\Cache\StorageFactory;
use Zend\Session\SaveHandler\Cache;
use Zend\Session\SessionManager;
use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\ServiceLocatorInterface;

use ZendAdditionals\Stdlib\ArrayUtils;

class SessionCacheInitializer
{
    const SERVICE_NS = 'RtsSessionCache\\';

    protected static $initialized = false;

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

        self::registerAdapters($serviceManager);

        self::$initialized = true;
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
            'rts_session_cache.adapters',
            $serviceManager->get('Config'),
            array()
        );

        if (array_key_exists('default', $adapters) === false) {
            throw new Exception\InvalidConfigException(
                "Missing default rts_session_cache.adapter.default configuration"
            );
        }

        foreach ($adapters as $name => $settings) {
            $service  = self::SERVICE_NS . $name;

            $serviceManager->setFactory($service, function() use ($settings) {
                $cache = StorageFactory::adapterFactory($settings['adapter']['name']);
                $cache->setOptions($settings['adapter']['options']);

                $saveHandler = new Cache($cache);

                $manager = new SessionManager();
                $manager->setSaveHandler($saveHandler);

                return $manager;
            });
        }
    }
}
