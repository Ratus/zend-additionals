<?php
namespace ZendAdditionals\Stdlib;

class IntUtils
{
    /**
     * Create as base62 encoded string from a integer
     *
     * @param integer $integer
     * @return string
     */
    public static function base62Encode($integer)
    {
        $ceiling   = 56800235584;   // pow(62,6)
        $baseChars = array_merge(range(48,57), range(65, 90), range(97,122));

        $string = '';
        while ($integer > 0) {
            $chr     = $integer - (floor($integer / 62) * 62);
            $integer = floor($integer / 62);
            $string .= chr($baseChars[$chr]);
        }

        return strrev($string);
    }

    /**
     * Generate short hash based on a Prime
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
        $base62 = self::base62Encode($base62Integer);

        return str_pad($base62, $length, "0", STR_PAD_LEFT);
    }
}
