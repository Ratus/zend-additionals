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
use Zend\Stdlib\Hydrator\ClassMethods;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * @category   ZendAdditionals
 * @package    ZendAdditionals_Stdlib
 * @subpackage Hydrator
 */
class ObservableClassMethods extends ClassMethods implements
    ObservableStrategyInterface,
    ServiceLocatorAwareInterface
{
    /**
     * @var ServiceLocatorInterface
     */
    protected $serviceLocator;

    /**
     * @var \SplObjectStorage
     */
    protected $objectStorage;

    protected function initializeEntityStorage()
    {
        if ($this->objectStorage instanceof \SplObjectStorage) {
            return;
        }
        $serviceLocator = $this->getServiceLocator();
        if (!($serviceLocator instanceof ServiceLocatorInterface)) {
            throw new \UnexpectedValueException(
                'The service locator must be set, actually did not expect this...'
            );
        }
        if (
            !$serviceLocator->has(
                'za_entity_storage'
            )
        ) {
            throw new \UnexpectedValueException('A service is required for entity storage!');
        } else {
            $this->objectStorage = $serviceLocator->get(
                'za_entity_storage'
            );
        }
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
        $extracted = parent::extract($object);
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
        $storage[$object] = parent::extract($object);
        return true;
    }

    /**
     * Hydrates the given data and makes sure the internal object storage makes a snapshot
     *
     * @see \Zend\Stdlib\Hydrator\ClassMethods::hydrate
     */
    public function hydrate(array $data, $object)
    {
        $object = parent::hydrate($data, $object);
        if (!empty($data)) {
            $this->getObjectStorage()->attach($object, $data); // store object relative to data
        }
        return $object;
    }

    /**
     * Get the service locator
     *
     * @return ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }

    /**
     * Set the service locator
     *
     * @param ServiceLocatorInterface $serviceLocator
     *
     * @return ObservableClassMethods
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
        return $this;
    }

}

