<?php

namespace ZendAdditionals\Stdlib\Hydrator\Strategy;

/**
 * @category   ZendAdditionals
 * @package    ZendAdditionals_Stdlib
 * @subpackage Hydrator
 */
interface ObservableStrategyInterface
{
    /**
     * Does this hydrator have the original data for the given value?
     *
     * @param mixed $value
     *
     * @return boolean
     */
    public function hasOriginal($value);

    /**
     * Extracts the original data for a specific value, this is only possible
     * when this value has been hydrated before.
     *
     * @param mixed $value
     *
     * @return mixed|boolean false on failure
     */
    public function extractOriginal($value);

    /**
     * Extracts the changed data for a specific value, the normal extract will be returned
     * when this entity was not hydrated by this instance.
     *
     * @param mixed $value
     *
     * @throws Exception\BadMethodCallException
     *
     * @return mixed
     */
    public function extractChanges($value);

    /**
     * When changes for a value have been committed to the original data source
     * these can be set as committed.
     *
     * @param mixed $value
     * @return boolean
     */
    public function setChangesCommitted($value);
}

