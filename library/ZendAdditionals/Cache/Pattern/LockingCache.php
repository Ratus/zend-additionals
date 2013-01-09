<?php

namespace ZendAdditionals\Cache\Pattern;

use Zend\Cache\Exception;
use Zend\Cache\Pattern\AbstractPattern;
use Zend\Cache\Pattern\PatternOptions;

class LockingCache extends AbstractPattern
{
    /**
     * Keep track of this instances locks
     * @var type
     */
    private $locks = array();

    /**
     * @var Zend\Cache\Storage
     */
    protected $storage;

    /**
     * Get a value or a list of values from memcached, false will be returned
     * when the value is not available, if multiple keys are provided
     * an array containing keys and values will be returned.
     *
     * @param mixed $key The key or an array of keys
     * @param function $callback [optional] When provided and the data is not available
     * the return of the callback function will be used to populate the data
     * @param int $ttl
     *
     * @return mixed The result, an array of results
     */
    public function get($key, $callback = null, $ttl = null)
    {
        $options = $this->getOptions();
        $enabled = $options->getEnabled();

        $result = null;

        if ($enabled) {
            $ttl = $ttl ?: $this->storage->getOptions()->getTtl();
            $result = $this->storage->getItem($key, $success);
        }

        $retryCount = 0;

        while ($enabled && $retryCount < $options->getRetryCount()) {
            if ($success && $this->isValid($result)) {
                // Raw data exists in cache
                if (!$this->isExpired($result) || $this->isLocked($key)) {
                    return $result['value'];
                } else {
                    // If data is available but expired and not locked, break the retry loop
                    break;
                }
            } else {
                // Raw data not available in cache
                if (!$this->isLocked($key)) {
                    // If the data is not available and not locked, break the retry loop
                    break;
                }
            }
            usleep(($options->getRetrySleep() * 1000));
            $result = $this->storage->getItem($key, $success);
            $retryCount++;
        }
        if (is_callable($callback)) {
            $locked = ($enabled ? $this->getLock($key) : false);
            $data = call_user_func($callback);
            if ($locked) {
                $this->set($key, $data, $ttl);
            }
            return $data;
        }
        return false;
    }

    /**
     * Get a lock for a specific key
     *
     * @param string $key
     * @param int $ttl
     * @return boolean
     */
    public function getLock($key, $ttl = null)
    {
        /**
         * Ignore locking when not enabled (this prevents sleeping code)
         */
        if (!$this->getOptions()->getEnabled()) {
            return true;
        }
        $lockKey = $this->getPreparedLockKey($key);
        $ttl = $ttl ?: $this->getOptions()->getLockTime();
        $currentlyLocked = $this->isLocked($key, $lockValue);
        if (
            !$currentlyLocked ||
            (
                isset($this->locks[$lockKey]) &&
                $this->locks[$lockKey] === $lockValue
            )
        ) {
            $originalTtl = $this->storage->getOptions()->getTtl();
            $this->storage->getOptions()->setTtl($ttl);
            $lockValue = mt_rand(0, mt_getrandmax());
            $preparedValue = $this->prepareValue($lockValue, $ttl);
            $success = $this->storage->setItem($lockKey, $preparedValue);
            $this->storage->getOptions()->setTtl($originalTtl);
            $this->locks[$lockKey] = $lockValue;
            return $success;
        }
        return false;
    }

    /**
     * Check if we have a lock on this key
     *
     * @param string $key
     * @return boolean
     */
    public function hasLock($key)
    {
        /**
         * Ignore locking when not enabled (this prevents sleeping code)
         */
        if (!$this->getOptions()->getEnabled()) {
            return false;
        }
        $lockKey = $this->getPreparedLockKey($key);
        if (!isset($this->locks[$lockKey])) {
            return false;
        }
        $currentlyLocked = $this->isLocked($key, $lockValue);
        if (!$currentlyLocked || $lockValue !== $this->locks[$lockKey]) {
            unset($this->locks[$lockKey]);
            return false;
        }
        return true;
    }

    /**
     * Release a lock on a key
     *
     * @param string $key
     * @param boolean $force Release the lock even if it does not belong to this instance
     * @return boolean
     */
    public function releaseLock($key, $force = false)
    {
        /**
         * Ignore locking when not enabled (this prevents sleeping code)
         */
        if (!$this->getOptions()->getEnabled()) {
            return true;
        }
        $lockKey = $this->getPreparedLockKey($key);
        $currentlyLocked = $this->isLocked($key, $lockValue);
        if (!$this->hasLock($key) && !$force) {
            return !$currentlyLocked;
        }
        if ($currentlyLocked && !$force && $this->locks[$lockKey] !== $lockValue ) {
            unset($this->locks[$lockKey]); // We don't own this lock anymore
            return false;
        }
        if ($currentlyLocked && !$this->storage->removeItem($lockKey)) {
            return false; // Releasing of the lock failed, lock remains
        }
        if (isset($this->locks[$lockKey])) {
            unset($this->locks[$lockKey]);
        }
        return true;
    }

    /**
     * Set a value into cache.
     *
     * @param string $key One key or an array of keys
     * @param mixed $value
     * @param int $ttl How long before the data gets expired?
     *
     * @return bool
     */
    public function set($key, $value, $ttl = null)
    {
        /**
         * Ignore setting when not enabled
         */
        if (!$this->getOptions()->getEnabled()) {
            return true;
        }
        $originalTtl = $this->storage->getOptions()->getTtl();
        $ttl = $ttl ?: $originalTtl;
        if ($this->hasLock($key)) {
            $preparedValue = $this->prepareValue($value, $ttl);
            $extraTtl = ($ttl + $this->getOptions()->getTtlBuffer());
            $this->storage->getOptions()->setTtl($extraTtl);
            $success = $this->storage->setItem($key, $preparedValue);
            $this->storage->getOptions()->setTtl($originalTtl);
            return $success;
        }
        return false;
    }

    /**
     * @param string $key
     * @return boolean
     */
    public function del($key)
    {
        /**
         * Ignore deleting when not enabled
         */
        if (!$this->getOptions()->getEnabled()) {
            return true;
        }
        return $this->storage->removeItem($key);
    }

    /**
     * Set options
     *
     * @param  PatternOptions $options
     * @return CallbackCache
     * @throws Exception\InvalidArgumentException if missing storage option
     */
    public function setOptions(PatternOptions $options)
    {
        parent::setOptions($options);
        // Prevent checking the storage adapter when not enabled
        if (!$options->getEnabled()) {
            return $this;
        }
        if (!($this->storage = $options->getStorage())) {
            throw new Exception\InvalidArgumentException('Missing option \'storage\'');
        }
        if (
            !($this->storage instanceof \Zend\Cache\Storage\Adapter\Memcached) &&
            !($this->storage instanceof \ZendAdditionals\Cache\Storage\Adapter\Memcache) &&
            !($this->storage instanceof \Zend\Cache\Storage\Adapter\Apc)
        ) {
            throw new Exception\InvalidArgumentException(
                'LockingCache requires storage to be a MemCached/Apc storage.'
            );
        }
        return $this;
    }

    /**
     * Prepare a value to be stored within cache
     *
     * @param mixed $value
     * @param int $ttl
     * @return array
     */
    protected function prepareValue($value, $ttl = null)
    {
        $ttl = $ttl ?: $this->storage->getOptions()->getTtl();
        return array(
            'ttl'       => $ttl,
            'timestamp' => time(),
            'value'     => $value,
        );
    }

    /**
     * Check if a lock exists for the given key
     *
     * @param string $key
     * @param int $lockValue
     * @return boolean
     */
    protected function isLocked($key, & $lockValue = null)
    {
        $lockKey = $this->getPreparedLockKey($key);
        $rawValue = $this->storage->getItem($lockKey, $success);
        $success = $success && $this->isValid($rawValue) && !$this->isExpired($rawValue);
        if ($success) {
            $lockValue = $rawValue['value'];
        }
        return $success;
    }

    /**
     * Check if an item is valid
     *
     * @param array $rawValue
     * @return boolean
     */
    protected function isValid(array $rawValue)
    {
        return (
            is_array($rawValue) &&
            isset($rawValue['timestamp']) &&
            isset($rawValue['ttl']) &&
            array_key_exists('value', $rawValue)
        );
    }

    /**
     * Check if an item is expired
     *
     * @param array $rawValue A valid rawValue array
     * @return boolean
     */
    protected function isExpired(array $rawValue)
    {
        $age = (int) (time() - (int)$rawValue['timestamp']);
        $ttl = (int) $rawValue['ttl'];
        return $age >= $ttl;
    }

    /**
     * Prepare a key to use for locking
     *
     * @param string $key
     * @return string
     */
    protected function getPreparedLockKey($key)
    {
        return $this->getOptions()->getLockPrefix() . $key;
    }
}

