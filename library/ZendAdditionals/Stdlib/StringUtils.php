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
}
