### Zend-Additionals Caching

## Configuration example for local.php - LockingCachePatternServiceFactory

**Note:** The servicefactory will look for zend_additions.locking_cache

```php
<?php
return array(
    'rts_cache' => return array(
        'patterns' => array(
            'invokables' => array(
                'locking' => 'ZendAdditionals\Cache\Pattern\LockingCache',
            ),
        ),
        'adapters' => array(
            'default' => array(
                'adapter' => array(
                    'name' => 'ZendAdditionals\\Cache\\Storage\\Adapter\\Memcache',
                    'options' => array(
                        'readable' => true,
                        'writable' => true,
                        'ttl' => 1800,
                        'servers' => array(),
                    ),
                ),
                'plugins' => array(),
                'pattern' => array(
                    'name'          => 'locking',
                    'options_class' => 'ZendAdditionals\Cache\Pattern\LockingPatternOptions',
                    'options' => array(
                        'retry_count' => 33,
                        'retry_sleep' => 75,
                        'lock_time'   => 10,
                        'lock_prefix' => 'lock_',
                        'ttl_buffer'  => 30,
                    ),
                ),
            ),
        ),
    ),
);
?>
```

### And in module.php
```php
<?php
namespace ZendAdditionals;

use ZendAdditionals\Cache\CacheInitializer;

class Module {
    /**
     * {@inheritdoc}
     */
    public function onBootstrap($event)
    {
        CacheInitializer::init(
            $event->getApplication()->getServiceManager()
        );
    }
}
```
