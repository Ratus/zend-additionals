<?php
namespace ZendAdditionals\Stdlib\Hydrator;

use Zend\Stdlib\Exception;

class ClassMethods extends \Zend\Stdlib\Hydrator\ClassMethods
{
    /**
     * Extract values from an object with class methods
     *
     * Extracts the getter/setter of the given $object.
     *
     * @param  object                           $object
     * @return array
     * @throws Exception\BadMethodCallException for a non-object $object
     */
    public function extract($object)
    {
        if (!is_object($object)) {
            throw new Exception\BadMethodCallException(sprintf(
                '%s expects the provided $object to be a PHP object)', __METHOD__
            ));
        }
        if ($object instanceof \DateTime) {
            return (array) $object;
        }
        $attributes = array();

        // Extraction only requires the get methods
        $methods = preg_grep('/^(get|is)/', get_class_methods($object));

        foreach ($methods as $method) {
            $substrStart = $method[0] == 'i' ? 0 : 4;
            if ($this->underscoreSeparatedKeys) {
                $attribute = strtolower(preg_replace('/(.)([A-Z])/', '$1_$2', $method));
                if ($substrStart > 0) {
                    $attribute = substr($attribute, $substrStart);
                }
            }
            $attributes[$attribute] = $this->extractValue($attribute, $object->$method());
        }

        return $attributes;
    }

    /**
     * Hydrate an object by populating getter/setter methods
     *
     * Hydrates an object by getter/setter methods of the object.
     *
     * @param  array                            $data
     * @param  object                           $object
     * @return object
     * @throws Exception\BadMethodCallException for a non-object $object
     */
    public function hydrate(array $data, $object)
    {
        if (!is_object($object)) {
            throw new Exception\BadMethodCallException(sprintf(
                '%s expects the provided $object to be a PHP object)', __METHOD__
            ));
        }

        $transform = function ($letters) {
            return strtoupper($letters[1]);
        };

        foreach ($data as $property => $value) {
            $method = "set_{$property}";
            if ($this->underscoreSeparatedKeys) {
                $method = preg_replace_callback('/_([a-z])/', $transform, $method);
                //$method = preg_replace('/_([a-z])/e', 'ucfirst("\\1")', $method);
            }
            if (method_exists($object, $method)) {
                $value = $this->hydrateValue($property, $value);

                $object->$method($value);
            }
        }


        return $object;
    }
}
