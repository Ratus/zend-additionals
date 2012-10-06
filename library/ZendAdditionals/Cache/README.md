### Zend-Additionals Caching

## Configuration example for local.php - LockingCachePatternServiceFactory

```php
array (
    'zend_additionals' => array(
        'locking_cache' => array(
            'storage_factory' => array(
                'adapter' => 'apc',
                'namespace' => 'myproject',
                'ttl'     => 1800,
            ),
            'pattern' => array(
                'retry_count' => 33,
                'retry_sleep' => 75,
                'lock_time'   => 10,
                'lock_prefix' => 'lock_',
                'ttl_buffer'  => 30,
            ),
        ),
    ),
 )
```