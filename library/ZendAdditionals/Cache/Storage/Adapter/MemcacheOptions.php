<?php
namespace ZendAdditionals\Cache\Storage\Adapter;

use Memcache as MemcacheResource;
use Zend\Cache\Exception;
use Zend\Cache\Storage\Adapter as StorageAdapter;

/**
 * @category    ZendAdditionals
 * @package     Cache
 * @subpackage  Storage\Adapter
 */
class MemcacheOptions extends StorageAdapter\AdapterOptions
{
    /**
     * @var MemcacheResource | NULL
     */
    protected $memcacheResource;

    /**
     * @var array
     */
    protected $servers = array();

    /**
     * @var array
     */
    protected $serversAdded = array();

    /**
     * A memcache resource to share
     *
     * @param MemcacheResource $memcache
     * @return self
     */
    public function setMemcacheResource(MemcacheResource $memcache = null) {
        if ($this->getMemcacheResource() !== $memcache) {
            $this->triggerOptionEvent('memcache_resource', $memcache);
            $this->memcacheResource = $memcache;
        }

        return $this;
    }

    /**
     * @var MemcacheResource | NULL
     */
    public function getMemcacheResource()
    {
        return $this->memcacheResource;
    }

    /**
     * Add a server to the list
     *
     * @param string $host
     * @param integer $port
     * @param boolean $persistent
     * @param integer $weight
     * @param integer $timeout
     * @param integer $retryInterval
     * @param boolean $status
     * @param callable $failureCallback
     * @return self
     */
    public function addServer(
        $host,
        $port            = 11211,
        $persistent      = true,
        $weight          = 0,
        $timeout         = 1,
        $retryInterval   = 15,
        $status          = true,
        $failureCallback = null
    ) {
        if (in_array("{$host}:{$port}", $this->serversAdded)) {
            return $this;
        }

        $serverInfo = new MemcacheServerInfo();
        $serverInfo->setHost($host)
            ->setPort($port)
            ->setPersistent($persistent)
            ->setWeight($weight)
            ->setTimeout($timeout)
            ->setRetryInterval($retryInterval)
            ->setStatus($status)
            ->setFailureCallback($failureCallback);

        $this->servers[] = $serverInfo;

        return $this;
    }

    /**
     * @return MemcacheServerInfo[]
     */
    public function getServers()
    {
        return $this->servers;
    }

    /**
     * Initial set servers
     *
     * @param array $servers
     * @return self
     */
    public function setServers(array $servers)
    {
        $this->servers = array();

        $default = array(
            'host'             => '',
            'port'             => 11211,
            'persistent'       => true,
            'weight'           => 1,
            'timeout'          => 1,
            'retry_interval'   => 15,
            'status'           => true,
            'failure_callback' => null
        );

        foreach ($servers as $server) {
            if (empty($server['host'])) {
                throw new Exception\InvalidArgumentException(
                    'The list of servers must contain a host value.'
                );
            }

            $setting = array_merge($default, $server);

            $this->addServer(
                $setting['host'],
                $setting['port'],
                $setting['persistent'],
                $setting['weight'],
                $setting['timeout'],
                $setting['retry_interval'],
                $setting['status'],
                $setting['failure_callback']
            );
        }

        return $this;
    }
}

