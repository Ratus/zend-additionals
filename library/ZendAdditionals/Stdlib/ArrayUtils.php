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
     * Merge two arrays together.
     *
     * If an integer key exists in both arrays, the value from the second array
     * will be appended the the first array (if it does not exist there yet!).
     * If both values are arrays, they are merged together, else the value
     * of the second array overwrites the one of the first array.
     *
     * @param  array $a
     * @param  array $b
     * @return array
     */
    public static function mergeDistinct(array $a, array $b)
    {
        foreach ($b as $key => $value) {
            if (array_key_exists($key, $a)) {
                if (is_int($key) && !in_array($value, $a, true)) {
                    $a[] = $value;
                } elseif (is_array($value) && is_array($a[$key])) {
                    $a[$key] = static::mergeDistinct($a[$key], $value);
                } else {
                    $a[$key] = $value;
                }
            } else {
                $a[$key] = $value;
            }
        }

        return $a;
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

    public static function toObject($data) {
		if (is_array($data)) {
			/*
			* Return array converted to object
			* Using __FUNCTION__ (Magic constant)
			* for recursive call
			*/
			return (object) array_map(__METHOD__, $data);
		}
		else {
			// Return object
			return $data;
		}
	}
}
