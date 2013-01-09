<?php
namespace ZendAdditionals\Cache\Storage\Adapter;

/**
 * @category    ZendAdditionals
 * @package     Cache
 * @subpackage  Storage\Adapter
 */
class MemcacheServerInfo
{
    /**
     * @var string
     */
    protected $host;

    /**
     * @var integer
     */
    protected $port;

    /**
     * @var boolean
     */
    protected $persistent;

    /**
     * @var integer
     */
    protected $weight;

    /**
     * @var integer
     */
    protected $timeout;

    /**
     * @var integer
     */
    protected $retryInterval;

    /**
     * @var boolean
     */
    protected $status;

    /**
     * @var callable
     */
    protected $failureCallback;


    /**
     * @param string $host
     * @return self
     */
    public function setHost($host)
    {
        $this->host = $host;
        return $this;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param integer $port
     * @return self
     */
    public function setPort($port)
    {
        $this->port = $port;
        return $this;
    }

    /**
     * @return integer
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param integer $timeout
     * @return self
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * @return integer
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * @param integer $retryInterval
     * @return self
     */
    public function setRetryInterval($retryInterval)
    {
        $this->retryInterval = $retryInterval;
        return $this;
    }

    /**
     * @return integer
     */
    public function getRetryInterval()
    {
        return $this->retryInterval;
    }

    /**
     * @param boolean $status
     * @return self
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param callable $failureCallback
     * @return self
     */
    public function setFailureCallback($failureCallback)
    {
        $this->failureCallback = $failureCallback;
        return $this;
    }

    /**
     * @return callable
     */
    public function getFailureCallback()
    {
        return $this->failureCallback;
    }

    /**
     * @param boolean $persistent
     * @return self
     */
    public function setPersistent($persistent)
    {
        $this->persistent = $persistent;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getPersistent()
    {
        return $this->persistent;
    }

    /**
     * @param integer $weight
     * @return self
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;
        return $this;
    }

    /**
     * @return integer
     */
    public function getWeight()
    {
        return $this->weight;
    }

}

