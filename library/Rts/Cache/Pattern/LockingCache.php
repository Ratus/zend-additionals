<?php

namespace Rts\Cache\Pattern;

use Zend\Cache\Exception;
use Zend\Cache\Pattern\AbstractPattern;
use Zend\Cache\Pattern\PatternOptions;

class LockingCache extends AbstractPattern
{
    /**
     * @var Zend\Cache\Storage
     */
    protected $storage;

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
        if (!($this->storage = $options->getStorage())) {
            throw new Exception\InvalidArgumentException("Missing option 'storage'");
        }
        if (
            !($this->storage instanceof \Zend\Cache\Storage\Adapter\Memcached) &&
            !($this->storage instanceof \Zend\Cache\Storage\Adapter\Apc)
        ) {
            throw new Exception\InvalidArgumentException("LockingCache requires storage to be a MemCached/Apc storage.");
        }
        return $this;
    }

    protected function isLocked($key)
    {
        return $this->storage->hasItem($this->getPrepareLockKey($key)) !== false;
    }

    protected function isValid(array $rawValue)
    {
        return (
            is_array($rawValue) &&
            isset($rawValue['timestamp']) &&
            isset($rawValue['expiration']) &&
            array_key_exists('value', $rawValue)
        );
    }

    protected function isExpired(array $rawValue)
    {
        $age = (int) (time() - (int)$rawValue['timestamp']);
        $ttl = (int) $rawValue['expiration'];
        return $age >= $ttl;
    }

    protected function getValue(array $rawValue)
    {
        return $rawValue['value'];
    }

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
        echo "Getting key: $key\n";
        $options = $this->getOptions();
        $ttl = $ttl ?: $this->storage->getOptions()->getTtl();

        $result = $this->storage->getItem($key, $success);
        $retryCount = 0;

        while ($retryCount < $options->getRetryCount()) {
            if ($success && $this->isValid($result)) {
                var_dump($result);
                // Raw data exists in cache
                if (!$this->isExpired($result) || $this->isLocked($key)) {
                    return $this->getValue($result);
                } else {
                    echo "Available, expired and not locked! \n";
                    // If data is available but expired and not locked, break the retry loop
                    break;
                }
            } else {
                // Raw data not available in cache
                if (!$this->isLocked($key)) {
                    echo "Not available and not locked! \n";
                    // If the data is not available and not locked, break the retry loop
                    break;
                }
            }
            echo "Not available or expired and locked. \n";
            usleep(($options->getRetrySleep() * 1000));
            $result = $this->storage->getItem($key, $success);
            $retryCount++;
        }
        if (is_callable($callback)) {
            $this->lock($key);
            $data = call_user_func($callback);
            $this->set($key, $data, $ttl);
            return $data;
        }
        return false;
    }

    public function lock($key, $ttl = null)
    {
        $key = $this->getPrepareLockKey($key);
        $originalTtl = $this->storage->getOptions()->getTtl();
        $ttl = $ttl ?: $this->getOptions()->getLockTime();

        $this->storage->getOptions()->setTtl($ttl);
        $success = $this->storage->setItem($key, true);
        $this->storage->getOptions()->setTtl($ttl);
        return $success;
    }

    protected function getPrepareLockKey($key)
    {
        return $this->getOptions()->getLockPrefix() . $key;
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
        $originalTtl = $this->storage->getOptions()->getTtl();
        $ttl = $ttl ?: $originalTtl;
        if ($this->lock($key)) {
            $preparedValue = array(
                'expiration' => $ttl,
                'timestamp'  => time(),
                'value'      => $value,
            );
            $extraTtl = ($ttl + $this->getOptions()->getTtlBuffer());
            $this->storage->getOptions()->setTtl($extraTtl);
            $success = $this->storage->setItem($key, $preparedValue);
            $this->storage->getOptions()->setTtl($originalTtl);
            return $success;
        } else {
            die('did not get lock!');
        }
    }
}

