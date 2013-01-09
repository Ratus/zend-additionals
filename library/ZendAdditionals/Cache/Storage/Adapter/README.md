### Zend-Additionals Caching

## Configuration example for local.php - LockingCachePatternServiceFactory

**Note:** The servicefactory will look for zend_additions.locking_cache

```php
$storage = \Zend\Cache\StorageFactory::adapterFactory(
    'ZendAdditionals\\Cache\\Storage\\Adapter\\Memcache'
);

$storage->setOptions(array(
    'servers' => array(
        array('host' => 'srvxxx'),
        array('host' => 'dev001.ratus.nl', 'port' => 22122, 'persistent' => false),
    ),
));
```

**note:** Available server keys and defaults
array(
    'host'             => **REQUIRED**,
    'port'             => 11211,
    'persistent'       => true,
    'weight'           => 1,
    'timeout'          => 1,
    'retry_interval'   => 15,
    'status'           => true,
    'failure_callback' => null
);

