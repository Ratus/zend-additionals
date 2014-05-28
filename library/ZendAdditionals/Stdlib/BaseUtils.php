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

    /**
     * Returns the formatted size
     *
     * @param  integer $size
     * 
     * @return string
     */
    public static function toByteString($size)
    {
        $sizes = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        for ($i=0; $size >= 1024 && $i < 9; $i++) {
            $size /= 1024;
        }

        return round($size, 2) . $sizes[$i];
    }

    /**
     * Returns the unformatted size
     *
     * @param  string $size
     * 
     * @return integer
     */
    public static function fromByteString($size)
    {
        if (is_numeric($size)) {
            return (int) $size;
        }

        $type  = trim(substr($size, -2, 1));

        $value = substr($size, 0, -1);
        if (!is_numeric($value)) {
            $value = substr($value, 0, -1);
        }

        switch (strtoupper($type)) {
            case 'Y':
                $value *= (1024 * 1024 * 1024 * 1024 * 1024 * 1024 * 1024 * 1024);
                break;
            case 'Z':
                $value *= (1024 * 1024 * 1024 * 1024 * 1024 * 1024 * 1024);
                break;
            case 'E':
                $value *= (1024 * 1024 * 1024 * 1024 * 1024 * 1024);
                break;
            case 'P':
                $value *= (1024 * 1024 * 1024 * 1024 * 1024);
                break;
            case 'T':
                $value *= (1024 * 1024 * 1024 * 1024);
                break;
            case 'G':
                $value *= (1024 * 1024 * 1024);
                break;
            case 'M':
                $value *= (1024 * 1024);
                break;
            case 'K':
                $value *= 1024;
                break;
            default:
                break;
        }

        return $value;
    }
}
