<?php
namespace ZendAdditionals\Stdlib;

use ArrayAccess;
use ZendAdditionals\Stdlib\Hydrator\ClassMethods;
use Zend\Stdlib\Hydrator\AbstractHydrator;

class ObjectUtils extends \Zend\Stdlib\ArrayUtils
{
    /**
     * Convert an object to an array
     *
     * @param object|array<object> $data
     * @param array                $map provide a prototype callback map like:
     * array(
     *      array(
     *          'prototype' => new \ZendAdditionals\Db\Entity\AttributeData,
     *          'callable'  => 'getValue',
     *          'callback'  => \Closure (first parameter is instance of prototype)
     *      ),
     *  ),
     * @param AbstractHydrator     $hydrator By default ClassMethods will be used
     *
     * @return array
     */
    public static function toArray(
        $data,
        array $map     = array(),
        array $filters = null,
        AbstractHydrator $hydrator = null
    ) {
        if (is_array($data) || $data instanceof ArrayAccess) {
            $return = array();
            foreach ($data as $key => $element) {
                $return[$key] = static::toArray($element, $map, $filters, $hydrator);
            }
            $data = $return;
        }
        if ($data instanceof ArrayAccess) {
            return (array) $data;
        }
        if (!is_object($data)) {
            return $data;
        }
        $hydrator = $hydrator ?: new ClassMethods;
        $array = $hydrator->extract($data);
        if (null !== $filters) {
            foreach ($filters as $instanceKey => $instanceFilter) {
                if ($data instanceof $instanceKey) {
                    $array = array_intersect_key(
                        $array,
                        array_flip($instanceFilter)
                    );
                }
            }
        }
        $keysHandledByMapping = array();
        foreach ($map as $objectMapping) {
            foreach ($array as $key => &$value) {
                if ($value instanceof $objectMapping['prototype']) {
                    if (isset($objectMapping['callable'])) {
                        $value = $value->{$objectMapping['callable']}();
                    } else if (
                        isset($objectMapping['callback']) &&
                        is_callable($objectMapping['callback'])
                    ) {
                        $value = $objectMapping['callback']($value);
                    }
                    $keysHandledByMapping[$key] = true;
                }
            }
        }
        foreach ($array as $key => &$value) {
            if (
                !isset($keysHandledByMapping[$key]) &&
                is_object($value)
            ) {
                $value = static::toArray($value, $map, $filters, $hydrator);
            }
        }

        return $array;
    }

    /**
     * Transfer all data information from one object into another
     * NOTE: objects must be uqual types
     *
     * @param object           $source
     * @param object           $target
     * @param AbstractHydrator $hydrator By default ClassMethods will be used
     *
     */
    public static function transferData(
                         $source,
                         $target,
        AbstractHydrator $hydrator  = null
    ) {
        if (
            !is_object($source) ||
            !is_object($target) ||
            !($source instanceof $target)
        ) {
            return false;
        }
        $hydrator   = $hydrator ?: new ClassMethods;
        $sourceData = $hydrator->extract($source);
        $targetData = $hydrator->extract($target);
        foreach ($sourceData as $key => $value) {
            if (is_object($value)) {
                if (null === $targetData[$key]) {
                    $targetData[$key] = new $sourceData[$key];
                }
                static::transferData(
                    $sourceData[$key],
                    $targetData[$key],
                    $hydrator
                );
            } else {
                $targetData[$key] = $sourceData[$key];
            }
        }
        $hydrator->hydrate($targetData, $target);
    }

    /**
     * {@inheritdoc}
     */
    public static function deepClone($data)
    {
        return ArrayUtils::deepClone($data);
    }
}
