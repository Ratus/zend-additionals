<?php
/**
 * Ratus Zend Additionals (http://www.ratus.nl/)
 *
 * @link      http://github.com/Ratus/ZendAdditionals
 * @copyright Copyright (c) 2005-2012 Ratus B.V. (http://www.ratus.nl)
 * @package   ZendAdditionals_Stdlib
 */

namespace ZendAdditionals\Stdlib\Hydrator;

use ZendAdditionals\Stdlib\Hydrator\Strategy\ObservableStrategyInterface;
use Zend\ServiceManager\ServiceManagerAwareInterface;
use Zend\ServiceManager\ServiceManager;

/**
 * @category   ZendAdditionals
 * @package    ZendAdditionals_Stdlib
 * @subpackage Hydrator
 */
class ObservableClassMethods extends ClassMethods implements
    ObservableStrategyInterface,
    ServiceManagerAwareInterface
{
    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * @var \SplObjectStorage
     */
    protected $objectStorage;

    protected $ignoreOriginalOnce = false;

    protected function initializeEntityStorage()
    {
        if ($this->objectStorage instanceof \SplObjectStorage) {
            return;
        }
        $serviceManager = $this->getServiceManager();
        if (!($serviceManager instanceof ServiceManager)) {
            throw new \UnexpectedValueException(
                'The service manager must be set, actually did not expect this...'
            );
        }
        if (
            !$serviceManager->has(
                'ZendAdditionals\Db\EntityStorage\Service\EntityStorage'
            )
        ) {
            $serviceManager->setFactory(
                'ZendAdditionals\Db\EntityStorage\Service\EntityStorage',
                'ZendAdditionals\Db\EntityStorage\Service\EntityStorageServiceFactory'
            );
        }
        $this->objectStorage = $serviceManager->get(
            'ZendAdditionals\Db\EntityStorage\Service\EntityStorage'
        );
        if (!($this->objectStorage instanceof \SplObjectStorage)) {
            throw new \UnexpectedValueException(
                'Did not get an \SplObjectStorage from the service locator.. bad...'
            );
        }
    }

    /**
     * @return \SplObjectStorage
     */
    public function getObjectStorage()
    {
        $this->initializeEntityStorage();
        return $this->objectStorage;
    }

    /**
     * Set the object storage
     *
     * @param \SplObjectStorage $objectStorage
     *
     * @return ObservableClassMethods
     */
    public function setObjectStorage(\SplObjectStorage $objectStorage)
    {
        $this->objectStorage = $objectStorage;
        return $this;
    }

    /**
     * Reinitialize the ObjectStorage (Can be handy with large imports :))
     *
     * @return self
     */
    public function resetObjectStorage()
    {
        if (($this->objectStorage instanceof \SplObjectStorage) === false) {
            return $this;
        }

        $this->objectStorage->rewind();

        foreach ($this->objectStorage as $item) {
            $this->objectStorage->detach($item);
            unset($item);
        }

        $this->objectStorage = null;
        $this->initializeEntityStorage();

        return $this;
    }

    /**
     * Does this hydrator have the original data for the given entity?
     *
     * @param mixed $object
     *
     * @return boolean
     */
    public function hasOriginal($object)
    {
        return $this->getObjectStorage()->contains($object);
    }

    /**
     * Extracts the original data for a specific entity, this is only possible
     * when this entity has been hydrated before.
     *
     * @param mixed $object
     *
     * @return array|boolean false on failure
     */
    public function extractOriginal($object)
    {
        if (!is_object($object)) {
            return false;
        }
        if ($this->getObjectStorage()->contains($object)) {
            $storage = $this->getObjectStorage();
            return $storage[$object];
        }
        return false;
    }

    public function extractRecursive($object)
    {
        $return = $this->extract($object);
        foreach ($return as $key => $possibleObject) {
            if (is_object($possibleObject)) {
                $return[$key] = $this->extractRecursive($possibleObject);
            }
        }
        return $return;
    }

    /**
     * Extracts the original data for a specific entity, the normal extract will be returned
     * when this entity was not hydrated by this instance.
     *
     * @param mixed $object
     *
     * @throws Exception\BadMethodCallException
     *
     * @return array
     */
    public function extractChanges($object)
    {
        $extracted = $this->extract($object);
        if ($this->getObjectStorage()->contains($object)) {
            $storage = $this->getObjectStorage();
            return array_udiff_assoc(
                $extracted,
                $storage[$object],
                array($this, 'isDataChanged')
            );
        }
        return $extracted;
    }

    /**
     * Compare mixed data in a strict way
     *
     * @param mixed $a
     * @param mixed $b
     *
     * @return boolean
     */
    public function isDataChanged($a, $b)
    {
        return $a !== $b;
    }

    /**
     *
     * @param mixed $object
     * @return boolean
     */
    public function setChangesCommitted($object)
    {
        $storage = $this->getObjectStorage();
        $storage[$object] = $this->extract($object);
        return true;
    }

    /**
     * Hydrates the given data and makes sure the internal object storage makes a snapshot (When original entity isn't found)
     *
     * @see \Zend\Stdlib\Hydrator\ClassMethods::hydrate
     */
    public function hydrate(array $data, $object)
    {
        $object = parent::hydrate($data, $object);
        if (
            !$this->ignoreOriginalOnce &&
            !empty($data) &&
            !$this->hasOriginal($object)
        ) {
            $this->getObjectStorage()->attach($object, $data); // store object relative to data
        }
        if ($this->ignoreOriginalOnce) {
            $this->ignoreOriginalOnce = false;
        }
        return $object;
    }

    /**
     * When hydrating the original data gets stored in spl object storage
     * this method will set a flag to ignore this storage once while hydrating
     *
     * @return \ZendAdditionals\Stdlib\Hydrator\ObservableClassMethods
     */
    public function setIgnoreOriginalOnce()
    {
        $this->ignoreOriginalOnce = true;
        return $this;
    }

    /**
     * Get the service manager
     *
     * @return ServiceManager
     */
    public function getServiceManager()
    {
        return $this->serviceManager;
    }

    /**
     * Set the service manager
     *
     * @param ServiceManager $serviceManager
     *
     * @return ObservableClassMethods
     */
    public function setServiceManager(ServiceManager $serviceManager)
    {
        $this->serviceManager = $serviceManager;
        return $this;
    }

}

