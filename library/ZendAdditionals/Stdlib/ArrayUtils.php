<?php
namespace ZendAdditionals\Stdlib;

use XMLWriter;

class ArrayUtils extends \Zend\Stdlib\ArrayUtils
{
    /**
     * Get an item from the config file
     *
     * @param string $needle   Dot separated string with the path you want
     * @param array  $haystack Dot separated string with the path you want
     * @param mixed  $default  The default value when the item has not be found
     * @example daemonizer.locations.pids => $config['daemonizer']['locations']['pids']
     *
     * @return mixed The value from the target | mixed on not found
     */
    public static function arrayTarget($needle, $haystack, $default = null)
    {
        // Split requested target
        $parts = explode('.', $needle);

        // Loop through the target
        foreach ($parts as $part) {
            // When not exists return default value
            if (array_key_exists($part, $haystack) === false) {
                return $default;
            }

            $haystack = $haystack[$part];
        }

        return $haystack;
    }

    /**
     * Merge two or more arrays together.
     *
     * If an integer key exists in both arrays, the value from the second array
     * will be appended the the first array. If both values are arrays, they
     * are merged together, else the value of the second array overwrites the
     * one of the first array.
     *
     * @param  \ArrayAccess|array $array1 Initial array to merge.
     * @param  \ArrayAccess|array $_      [optional] Variable list of arrays to recursively merge.
     *
     * @return \ArrayAccess|array
     */
    public static function mergeAll($array1, $_ = null)
    {
        $arrays = func_get_args();
        array_shift($arrays);
        if (empty($arrays)) {
            return $array1;
        }
        foreach ($arrays as $b) {
            foreach ($b as $key => $value) {
                if (array_key_exists($key, $array1)) {
                    if (is_int($key)) {
                        $array1[] = $value;
                    } elseif (
                        (
                            is_array($value) ||
                            ($value instanceof \ArrayAccess)
                        ) &&
                        (
                            is_array($array1[$key]) ||
                            ($array1[$key] instanceof \ArrayAccess)
                        )
                    ) {
                        $array1[$key] = static::mergeAll($array1[$key], $value);
                    } else {
                        $array1[$key] = $value;
                    }
                } else {
                    $array1[$key] = $value;
                }
            }
        }
        return $array1;
    }

    /**
     * Merge two arrays together.
     *
     * If an integer key exists in both arrays, the value from the second array
     * will be appended the the first array (if it does not exist there yet!).
     * If both values are arrays, they are merged together, else the value
     * of the second array overwrites the one of the first array.
     *
     * @param  array $array1 Initial array to merge.
     * @param  array $_      [optional] Variable list of arrays to recursively merge.
     *
     * @return array
     */
    public static function mergeDistinct(array $array1, array $_ = null)
    {
        $arrays = func_get_args();
        array_shift($arrays);
        if (empty($arrays)) {
            return $array1;
        }
        foreach ($arrays as $b) {
            foreach ($b as $key => $value) {
                if (array_key_exists($key, $array1)) {
                    if (is_int($key) && !in_array($value, $array1, true)) {
                        $array1[] = $value;
                    } elseif (is_array($value) && is_array($array1[$key])) {
                        $array1[$key] = static::mergeDistinct($array1[$key], $value);
                    } else {
                        $array1[$key] = $value;
                    }
                } else {
                    $array1[$key] = $value;
                }
            }
        }
        return $array1;
    }

    /**
     * Generate xml from an array
     *
     * @param array  $data The data to convert to xml
     * @param string $root The name of the root element
     *
     * @return string The generated xml
     */
    public static function toXml(array $data, $root = 'data')
    {
        $writer = new XMLWriter('UTF-8');
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString(str_repeat(' ', 4));

        $writer->startDocument('1.0', 'UTF-8');
        $writer->startElement($root);

        foreach ($data as $sectionName => $value) {
            if (!is_array($value)) {
                $writer->writeElement($sectionName, (string) $value);
            } else {
                $this->addBranch($sectionName, $value, $writer);
            }
        }

        $writer->endElement();
        $writer->endDocument();

        return $writer->outputMemory();
    }

    /**
     * Creates an xml document entity for a sphinx xml feed
     *
     * @param array $data       Must contain an 'id' field for the sphinx document id
     * @param array $fields     {@see SphinxXMLWriter::setFields}
     * @param array $attributes {@see SphinxXMLWriter::setAttributes}
     * @return string
     */
    public static function toSphinxXmlPipe2(
        array $data,
        array $fields     = null,
        array $attributes = null
    ) {
        $writer = new \ZendAdditionals\Xml\Writer\SphinxXMLWriter('UTF-8');
        if (!empty($fields)) {
            $writer->setFields($fields);
        }
        if (!empty($attributes)) {
            $writer->setAttributes($attributes);
        }
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString(str_repeat(' ', 4));

        // Add an array as document to the sphinx xml
        $writer->addDocument($data);

        return $writer->outputMemory();
    }

    /**
     * Convert an array to an \stdObject
     *
     * @param mixed $data
     *
     * @return \stdObject
     */
    public static function toObject($data) 
    {
        if (is_array($data)) {
            /*
            * Return array converted to object
            * Using __METHOD__ (Magic constant)
            * for recursive call
            */
            return (object) array_map(__METHOD__, $data);
        }
        else {
            // Return object
            return $data;
        }
    }
    
    /**
     * Convert a multi dimensional array to a flat array
     * 
     * @param array $array
     * 
     * @return array
     */
    public static function flatten(array $array)
    {
        $return = array();
        array_walk_recursive(
            $array, 
            function($a) use (&$return) { 
                $return[] = $a; 
            }
        );
        return $return;
    }
}
