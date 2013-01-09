<?php
namespace ZendAdditionals\Session;

use SessionHandlerInterface;
use ZendAdditionals\Cache\Pattern\LockingCache;

/**
 * @category    ZendAdditonals
 * @package     Session
 */
class Handler implements SessionHandlerInterface
{
    /**
     * @var LockingCache
     */
    protected $lockingCache;

    /**
     * @param LockingCache $lockingCache
     * @return Handler
     */
    public function __construct(LockingCache $lockingCache)
    {
        $this->setLockingCache($lockingCache);
    }

    /**
     * @param LockingCache $lockingCache
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

    /**
     * Method when closing session
     *
     * @return boolean
     */
    public function close()
    {
        return true;
    }

    /**
     * Method called when session_destoy has been called
     *
     * @param string $sessionId
     * @return boolean
     */
    public function destroy($sessionId)
    {
        if ($this->getLockingCache()->getLock($sessionId) === false) {
            return false;
        }

        $this->getLockingCache()->del($sessionId);

        return $this->getLockingCache()->releaseLock($sessionId);
    }

    /**
     * Garbage collector
     *
     * @param integer $maxLifeTime
     * @return boolean
     */
    public function gc($maxLifeTime)
    {
        return true;
    }

    /**
     * When opening the session
     *
     * @param string $savePath
     * @param string $name
     * @return boolean
     */
    public function open($savePath, $name)
    {
        return true;
    }

    /**
     * Read the current session from cache and return the content
     *
     * @param string $sessionId
     * @return boolean
     */
    public function read($sessionId)
    {
        if ($this->getLockingCache()->getLock($sessionId) === false) {
            return '';
        }

        $return = $this->getLockingCache()->get($sessionId);

        error_log(__METHOD__.'::'.$return);

        if ($return === false) {
            return '';
        }

        $this->getLockingCache()->releaseLock($sessionId);


        return $return;
    }

    /**
     * Write the sessiondata to the cache
     *
     * @param string $sessionId
     * @param string $sessionData
     * @return boolean
     */
    public function write($sessionId, $sessionData)
    {
        if ($this->getLockingCache()->getLock($sessionId) === false) {
            return false;
        }

        error_log(__METHOD__.'::'.$sessionData);

        $this->getLockingCache()->set($sessionId, $sessionData);

        return $this->getLockingCache()->releaseLock($sessionId);
    }
}

