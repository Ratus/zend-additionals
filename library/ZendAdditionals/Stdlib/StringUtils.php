<?php
namespace ZendAdditionals\Stdlib;

class StringUtils extends \Zend\Stdlib\StringUtils
{
    /**
     * Convert an underscored string to a camelcased string
     *
     * @param  string $underscored e.g.: get_some_value
     *
     * @return string e.g.: getSomeValue
     */
    public static function underscoreToCamelCase($underscored)
    {
        $underscored = strtolower($underscored);
        return preg_replace('/_(.?)/e', "strtoupper('$1')", $underscored);
    }

    /**
     * Check value to find if it was serialized.
     *
     * If $data is not an string, then returned value will always be false.
     * Serialized data is always a string.
     *
     * @param mixed $data Value to check to see if was serialized.
     *
     * @return bool false if not serialized and true if it was.
     */
    public static function isSerialized($data)
    {
        // if it isn't a string, it isn't serialized
        if (!is_string($data)) {
            return false;
        }
        $data = trim($data);
        if ('N;' == $data) {
            return true;
        }
        $length = strlen($data);
        if ($length < 4) {
            return false;
        }
        if (':' !== $data[1]) {
            return false;
        }
        $lastc = $data[$length-1];
        if (';' !== $lastc && '}' !== $lastc) {
            return false;
        }
        $token = $data[0];
        switch ($token) {
            case 's':
                if ('"' !== $data[$length-2]) {
                    return false;
                }
            case 'a':
            case 'O':
                return (bool) preg_match("/^{$token}:[0-9]+:/s", $data);
            case 'b':
            case 'i':
            case 'd':
                return (bool) preg_match("/^{$token}:[0-9.E-]+;\$/", $data);
        }
        return false;
    }
}
