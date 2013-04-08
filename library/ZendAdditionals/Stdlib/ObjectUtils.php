<?php
namespace ZendAdditionals\Stdlib;

use Zend\Stdlib\Hydrator\AbstractHydrator;

class ObjectUtils extends \Zend\Stdlib\ArrayUtils
{
    /**
     * Convert an object to an array
     *
     * @param object|array<object> $data
     * @param array                $map provide a prototype callback map like:
     * @param AbstractHydrator     $hydrator By default ClassMethods will be used
     * array(
     *      array(
     *          'prototype' => new \ZendAdditionals\Db\Entity\AttributeData,
     *          'callable'  => 'getValue',
     *      ),
     *  ),
     *
     * @return array
     */
    public static function toArray(
        $data,
        array $map     = array(),
        array $filters = null,
        AbstractHydrator $hydrator = null
    ) {
        if (is_array($data) || $data instanceof \ArrayAccess) {
            foreach ($data as &$element) {
                $element = static::toArray($element, $map, $filters, $hydrator);
            }
        }
        if ($data instanceof \ArrayAccess) {
            return (array) $data;
        }
        if (!is_object($data)) {
            return $data;
        }
        $hydrator = $hydrator ?: new \ZendAdditionals\Stdlib\Hydrator\ClassMethods;
        $array = $hydrator->extract($data);
        if (null !== $filters) {
            foreach ($filters as $instanceKey => $instanceFilter) {
                if ($data instanceof $instanceKey) {
                    $array = array_intersect_key($array, array_flip($instanceFilter));
                }
            }
        }
        foreach ($map as $objectMapping) {
            foreach ($array as $key => &$value) {
                if ($value instanceof $objectMapping['prototype']) {
                    $value = $value->{$objectMapping['callable']}();
                } elseif (is_object($value)) {
                    $value = static::toArray($value, $map, $filters, $hydrator);
                }
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
    public static function transferData($source, $target, AbstractHydrator $hydrator = null)
    {
        if (!is_object($source) || !is_object($target) || !($source instanceof $target)) {
            return false;
        }
        $hydrator   = $hydrator ?: new \ZendAdditionals\Stdlib\Hydrator\ClassMethods;
        $sourceData = $hydrator->extract($source);
        $targetData = $hydrator->extract($target);
        foreach ($sourceData as $key => $value) {
            if (is_object($value)) {
                static::transferData($sourceData[$key], $targetData[$key]);
            } else {
                $targetData[$key] = $sourceData[$key];
            }
        }
        $hydrator->hydrate($targetData, $target);
    }
}
