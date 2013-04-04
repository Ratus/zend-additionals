<?php
namespace ZendAdditionals\Cache;

use ZendAdditionals\Cache\Pattern\LockingCache;

trait LockingCacheAwareTrait
{
    /**
     * @var LockingCache
     */
    protected $lockingCache;

    /**
     * @param  LockingCache $lockingCache
     *
     * @return self
     */
    public function setLockingCache(LockingCache $lockingCache)
    {
        $this->lockingCache = $lockingCache;
        return $this;
    }

    /**
     * @return LockingCache
     */
    public function getLockingCache()
    {
        return $this->lockingCache;
    }
}
