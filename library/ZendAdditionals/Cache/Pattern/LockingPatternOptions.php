<?php

namespace ZendAdditionals\Cache\Pattern;

class LockingPatternOptions extends \Zend\Cache\Pattern\PatternOptions
{
    protected $retryCount = 30;
    protected $retrySleep = 150;
    protected $lockTime   = 5;
    protected $lockPrefix = 'lock_';
    protected $ttlBuffer  = 30;

    public function setEnabled($enabled)
    {
        $this->enabled = (boolean)$enabled;
    }

    public function getEnabled()
    {
        return $this->enabled;
    }

    public function setRetryCount($count)
    {
        $this->retryCount = $count;
    }

    public function getRetryCount()
    {
        return $this->retryCount;
    }

    public function setRetrySleep($sleepTime)
    {
        $this->retrySleep = $sleepTime;
    }

    public function getRetrySleep()
    {
        return $this->retrySleep;
    }

    public function setLockTime($time)
    {
        $this->lockTime = $time;
    }

    public function getLockTime()
    {
        return $this->lockTime;
    }

    public function setLockPrefix($prefix)
    {
        $this->lockPrefix = $prefix;
    }

    public function getLockPrefix()
    {
        return $this->lockPrefix;
    }

    public function setTtlBuffer($buffer)
    {
        $this->ttlBuffer = $buffer;
    }

    public function getTtlBuffer()
    {
        return $this->ttlBuffer;
    }
}

