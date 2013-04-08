<?php
namespace ZendAdditionals\Stdlib;

class BaseUtils
{
    /**
     * Create as base62 encoded string from a integer
     *
     * @param integer $integer
     * @return string
     */
    public static function base62Encode($integer)
    {
        return self::baseEncode($integer, 62);
    }

    /**
     * General base encoder
     *
     * @param integer $integer
     * @param integer $base
     */
    public static function baseEncode($integer, $base)
    {
        $baseChars = array_merge(range(48,57), range(97,122), range(65, 90));

        $string = '';
        while ($integer > 0) {
            $chr     = $integer - (floor($integer / $base) * $base);
            $integer = floor($integer / $base);
            $string .= chr($baseChars[$chr]);
        }

        return strrev($string);
    }

    /**
     * Generate short hash based on a Prime
     * Note: Choose high prime number for better security
     *
     * @param  integer $integer
     * @param  integer $prime
     * @param  integer $length
     * @return string
     */
    public static function base62Hash($integer, $prime, $length = 6)
    {
        $ceiling       = pow(62, $length);
        $base62Integer = ($integer * $prime) - floor($integer * $prime / $ceiling) * $ceiling;
        $base62        = self::base62Encode($base62Integer);

        return str_pad($base62, $length, "0", STR_PAD_LEFT);
    }
}
