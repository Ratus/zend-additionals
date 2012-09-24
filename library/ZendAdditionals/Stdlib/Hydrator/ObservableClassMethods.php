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

/**
 * @category   ZendAdditionals
 * @package    ZendAdditionals_Stdlib
 * @subpackage Hydrator
 */
class ObservableClassMethods extends ClassMethods implements ObservableStrategyInterface
{
    /**
     * @var \SplObjectStorage
     */
    protected $objectStorage;

    protected $diff = array();

    public function __construct($underscoreSeparatedKeys = true)
    {
        $this->objectStorage = new \SplObjectStorage();
        parent::__construct($underscoreSeparatedKeys);
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
        return $this->objectStorage->contains($object);
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
        if ($this->objectStorage->contains($object)) {
            return $this->objectStorage[$object];
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
        if ($this->objectStorage->contains($object)) {
            return array_udiff_assoc($extracted, $this->objectStorage[$object], array($this, 'isDataChanged'));
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
        $this->objectStorage[$object] = parent::extract($object);
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
            $this->objectStorage->attach($object, $data); // store object relative to data
        }
        return $object;
    }

}

