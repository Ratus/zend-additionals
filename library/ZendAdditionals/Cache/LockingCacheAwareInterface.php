<?php
namespace ZendAdditionals\Cache;

use ZendAdditionals\Cache\Pattern\LockingCache;

interface LockingCacheAwareInterface
{
    /**
     * @param LockingCache $lockingCache
     *
     * @return self
     */
    public function setLockingCache(LockingCache $lockingCache);

    /**
     * @return LockingCache
     */
    public function getLockingCache();
}
