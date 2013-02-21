<?php
namespace ZendAdditionals\Cache\Storage\Adapter;

use \Memcache as MemcacheResource;
use Zend\Cache\Storage\Adapter\AbstractAdapter;
use Zend\Cache\Storage\Capabilities;
use Zend\Cache\Storage\FlushableInterface;
use Zend\Cache\Exception;

/**
 * @category    ZendAdditionals
 * @package     Cache
 * @subpackage  Storage\Adapter
 */
class Memcache extends AbstractAdapter implements FlushableInterface
{
    /**
     * @var MemcacheResource
     */
    protected $memcacheResource;

    /**
     * @var MemcacheResource
     */
    public function getMemcacheResource()
    {
        if ($this->memcacheResource !== null) {
            return $this->memcacheResource;
        }

        $options  = $this->getOptions();
        $memcache = $options->getMemcacheResource() ?: new MemcacheResource();
        $servers  = $options->getServers();

        // Initialize servers
        foreach($servers as $server) {
            $memcache->addServer(
                $server->getHost(),
                $server->getPort(),
                $server->getPersistent(),
                $server->getWeight(),
                $server->getTimeout(),
                $server->getRetryInterval(),
                $server->getStatus(),
                $server->getFailureCallback()
            );
        }

        $this->memcacheResource = $memcache;

        return $this->memcacheResource;
    }

    /**
     * Get options
     *
     * @return MemcacheOptions
     */
    public function getOptions()
    {
        if (!$this->options) {
            $this->setOptions(new MemcacheOptions());
        }

        return $this->options;
    }

    /**
     * Set options.
     *
     * @param  array|Traversable|MemcachedOptions $options
     * @return Memcache
     * @see    getOptions()
     */
    public function setOptions($options)
    {
        if (($options instanceOf MemcacheOptions) === false) {
            $options = new MemcacheOptions($options);
        }

        return parent::setOptions($options);
    }

    /**
     * Flush the whole storage
     *
     * @return boolean
     */
    public function flush()
    {
        if ($this->getMemcacheResource()->flush() === false) {
            throw new Exception\RuntimeException(
                "Could not flush memcache"
            );
        }

        return true;
    }

    /**
     * Internal method to get an item
     *
     * @param string $normalizedKey
     * @param mixed $success
     * @param mixed $casToken   (NOT used)
     * @return mixed
     */
    protected function internalGetItem(&$normalizedKey, &$success = NULL, &$casToken = NULL) {
        $result  = $this->getMemcacheResource()->get($normalizedKey);
        $success = true;

        if ($result === false) {
            $success = false;
            $result  = null;
        }

        return $result;
    }

    /**
     * Internal method to set an item
     *
     * @param string $normalizedKey
     * @param mixed $value
     * @return boolean
     */
    protected function internalSetItem(&$normalizedKey, &$value)
    {
        $expiration = $this->expirationTime();
        $set        = $this->getMemcacheResource()
                        ->set($normalizedKey, $value, false, $expiration);

        if ($set === false) {
            throw new Exception\RuntimeException(
                "Could not set value for key `{$normalizedKey}`"
            );
        }

        return true;
    }

    /**
     * Internal method to replace an item
     *
     * @param boolean $normalizedKey
     * @param mixed $value
     * @return boolean
     */
    protected function internalReplaceItem(&$normalizedKey, &$value)
    {
        $expiration = $this->expirationTime();
        $replace    = $this->getMemcacheResource()
                        ->set($normalizedKey, $value, false, $expiration);

        if ($replace === false) {
            throw new Exception\RuntimeException(
                "Could not replace value `{$value}` for key `{$key}`"
            );
        }

        return true;
    }

    /**
     * Internal method to remove an item
     *
     * @param string $normalizedKey
     * @return boolean
     */
    protected function internalRemoveItem(&$normalizedKey)
    {
        return $this->getMemcacheResource()->delete($normalizedKey);
    }

    /**
     * Internal method to increment an item.
     *
     * @param  string $normalizedKey
     * @param  int    $value
     * @return int|bool The new value on success, false on failure
     */
    protected function internalIncrementItem(&$normalizedKey, &$value)
    {
        $value    = (int) $value;
        $newValue = $this->getMemcachedResource()->increment($normalizedKey, $value);

        if ($newValue === false) {
            $this->internalSetItem($normalizedKey, $value);
        }

        return $newValue;
    }

    /**
     * Internal method to decrement an item.
     *
     * @param  string $normalizedKey
     * @param  int    $value
     * @return int|bool The new value on success, false on failure
     */
    protected function internalDecrementItem(&$normalizedKey, &$value)
    {
        $value    = (int) $value;
        $newValue = $this->getMemcachedResource()->decrement($normalizedKey, $value);

        if ($newValue === false) {
            $this->internalSetItem($normalizedKey, -$value);
        }

        return $newValue;
    }

    /**
     * Get expiration time by ttl
     *
     * Some storage commands involve sending an expiration value (relative to
     * an item or to an operation requested by the client) to the server. In
     * all such cases, the actual value sent may either be Unix time (number of
     * seconds since January 1, 1970, as an integer), or a number of seconds
     * starting from current time. In the latter case, this number of seconds
     * may not exceed 60*60*24*30 (number of seconds in 30 days); if the
     * expiration value is larger than that, the server will consider it to be
     * real Unix time value rather than an offset from current time.
     *
     * @return int
     */
    protected function expirationTime()
    {
        $ttl = $this->getOptions()->getTtl();
        if ($ttl > 2592000) {
            return time() + $ttl;
        }
        return $ttl;
    }

    /**
     * Internal method to get capabilities of this adapter
     *
     * @return Capabilities
     */
    protected function internalGetCapabilities()
    {
        if ($this->capabilities === null) {
            $this->capabilityMarker = new stdClass();
            $this->capabilities     = new Capabilities(
                $this,
                $this->capabilityMarker,
                array(
                    'supportedDatatypes' => array(
                        'NULL'     => true,
                        'boolean'  => true,
                        'integer'  => true,
                        'double'   => true,
                        'string'   => true,
                        'array'    => true,
                        'object'   => 'object',
                        'resource' => false,
                    ),
                    'supportedMetadata'  => array(),
                    'minTtl'             => 1,
                    'maxTtl'             => 0,
                    'staticTtl'          => true,
                    'ttlPrecision'       => 1,
                    'useRequestTime'     => false,
                    'expiredRead'        => false,
                    'maxKeyLength'       => 255,
                    'namespaceIsPrefix'  => true,
                )
            );
        }

        return $this->capabilities;
    }
}

