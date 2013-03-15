<?php
namespace ZendAdditionals\Stdlib;

use Zend\Stdlib\Hydrator\AbstractHydrator;

class ObjectUtils extends \Zend\Stdlib\ArrayUtils
{
    /**
     * Convert an object to an array
     *
     * @param object           $data
     * @param array            $map provide a prototype callback map like:
     * @param AbstractHydrator $hydrator By default ClassMethods will be used
     * array(
     *      array(
     *          'prototype' => new \ZendAdditionals\Db\Entity\AttributeData,
     *          'callable'  => 'getValue',
     *      ),
     *  ),
     *
     * @return \stdObject
     */
    public static function toArray(
        $object,
        array $map = array(),
        AbstractHydrator $hydrator = null
    ) {
        if (!is_object($object)) {
            return $object;
        }
        $hydrator = $hydrator ?: new \Zend\Stdlib\Hydrator\ClassMethods;
        $array = $hydrator->extract($object);
        foreach ($map as $objectMapping) {
            foreach ($array as $key => &$value) {
                if ($value instanceof $objectMapping['prototype']) {
                    $value = $value->{$objectMapping['callable']}();
                } elseif (is_object($value)) {
                    $value = static::toArray($value, $map, $hydrator);
                }
            }
        }
        return $array;
    }
}
