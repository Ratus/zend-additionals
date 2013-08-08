<?php
namespace ZendAdditionals\Stdlib;

use ZendAdditionals\Stdlib\ArrayUtils;

class StringUtils extends \Zend\Stdlib\StringUtils
{
    const NL_NIX = "\n";
    const NL_WIN = "\r\n";
    const NL_MAC = "\r";

    /**
     * @var boolean
     */
    protected static $uRandomChecked = false;

    /**
     * @var resource
     */
    protected static $uRandomResource;

    /**
     * @brief Generates a Universally Unique IDentifier, version 4.
     *
     * This function generates a truly random UUID.
     *
     * @see http://tools.ietf.org/html/rfc4122#section-4.4
     * @see http://en.wikipedia.org/wiki/UUID
     *
     * @return string A UUID, made up of 32 hex digits and 4 hyphens.
     */
    public static function generateUuid()
    {
        $randomBits = false;
        if (!static::$uRandomChecked && !is_resource(static::$uRandomResource)) {
            if (is_readable('/dev/urandom')) {
                static::$uRandomResource = fopen('/dev/urandom', 'rb');
            }
            static::$uRandomChecked = true;
        }
        if (is_resource(static::$uRandomResource)) {
            $randomBits = fread(static::$uRandomResource, 16);
        }
        if (false === $randomBits) {
            // If /dev/urandom isn't available (eg: in non-unix systems), use mt_rand().
            $randomBits = "";
            for($count = 0; $count < 16; $count++) {
                $randomBits .= chr(mt_rand( 0, 255 ));
            }
        }
        $timeLow                    = bin2hex(substr($randomBits,  0, 4));
        $timeMid                    = bin2hex(substr($randomBits,  4, 2));
        $timeHiAndVersion           = bin2hex(substr($randomBits,  6, 2));
        $clockSequenseHiAndReversed = bin2hex(substr($randomBits,  8, 2));
        $node                       = bin2hex(substr($randomBits, 10, 6));

        /**
         * Set the four most significant bits (bits 12 through 15) of the
         * timeHighAndVersion field to the 4-bit version number from
         * Section 4.1.3.
         * @see http://tools.ietf.org/html/rfc4122#section-4.1.3
         */
        $timeHiAndVersion = hexdec($timeHiAndVersion);
        $timeHiAndVersion = $timeHiAndVersion >> 4;
        $timeHiAndVersion = $timeHiAndVersion | 0x4000;

        /**
         * Set the two most significant bits (bits 6 and 7) of the
         * clock_seq_hi_and_reserved to zero and one, respectively.
         */
        $clockSequenseHiAndReversed = hexdec($clockSequenseHiAndReversed);
        $clockSequenseHiAndReversed = $clockSequenseHiAndReversed >> 2;
        $clockSequenseHiAndReversed = $clockSequenseHiAndReversed | 0x8000;

        return sprintf(
            '%08s-%04s-%04x-%04x-%012s',
            $timeLow,
            $timeMid,
            $timeHiAndVersion,
            $clockSequenseHiAndReversed,
            $node
        );
    }

    /**
     * Normalize the path
     *
     * @param string $path
     * @param string $directorySeparator
     * @return string
     */
    public static function normalizeDirectorySeparator($path, $directorySeparator = null)
    {
        $directorySeparator = $directorySeparator ?: DIRECTORY_SEPARATOR;

        return str_replace(array('/', '\\'), $directorySeparator, $path);
    }

    /**
     * Convert an underscored string to a camelcased string
     *
     * @param  string $underscored e.g.: get_some_value
     *
     * @return string e.g.: getSomeValue
     */
    public static function underscoreToCamelCase($underscored)
    {
        static $runtimeCache = array();

        if (array_key_exists($underscored, $runtimeCache) === false) {
            $runtimeCache[$underscored] = preg_replace(
                '/_(.?)/e', "strtoupper('$1')",
                $underscored
            );
        }

        return $runtimeCache[$underscored];
    }

    /**
     * Convert camelcased string to underscored string
     *
     * @param  string $needle
     * @return string
     */
    public static function camelCaseToUnderscore($needle)
    {
        static $runtimeCache = array();

        if (array_key_exists($needle, $runtimeCache) === false) {
            $runtimeCache[$needle] = preg_replace('/([A-Z])/e', "strtolower('_$1')", $needle);
        }

        return $runtimeCache[$needle];
    }

    /**
     * Check value to find if it was Json Encoded.
     *
     * If $data is not a string, the returned value will always be false.
     * Json Encoded data is always a string.
     *
     * @param mixed $data Value to check to see if was Json Encoded.
     *
     * @return boolean false if not Json Encoded and true if it was.
     */
    public static function isJson($data)
    {
        // if it isn't a string, it isn't json
        if (!is_string($data)) {
            return false;
        }

        $pcre_regex = '
            /
            (?(DEFINE)
               (?<number>   -? (?= [1-9]|0(?!\d) ) \d+ (\.\d+)? ([eE] [+-]? \d+)? )
               (?<boolean>   true | false | null )
               (?<string>    " ([^"\\\\]* | \\\\ ["\\\\bfnrt\/] | \\\\ u [0-9a-f]{4} )* " )
               (?<array>     \[  (?:  (?&json)  (?: , (?&json)  )*  )?  \s* \] )
               (?<pair>      \s* (?&string) \s* : (?&json)  )
               (?<object>    \{  (?:  (?&pair)  (?: , (?&pair)  )*  )?  \s* \} )
               (?<json>   \s* (?: (?&number) | (?&boolean) | (?&string) | (?&array) | (?&object) ) \s* )
            )
            \A (?&json) \Z
            /six
        ';

        return preg_match(
            $pcre_regex,
            $data
        ) >= 1;
    }

    /**
     * Check value to find if it was serialized.
     *
     * If $data is not a string, the returned value will always be false.
     * Serialized data is always a string.
     *
     * @param mixed $data Value to check to see if was serialized.
     *
     * @return boolean false if not serialized and true if it was.
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
        $lastc = $data[$length - 1];
        if (';' !== $lastc && '}' !== $lastc) {
            return false;
        }
        $token = $data[0];
        switch ($token) {
            case 's':
                if ('"' !== $data[$length - 2]) {
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

    /**
     * Get a crc62 string from any string
     *
     * @param string $source
     *
     * @return string|boolean False on failure, base 62 string on success
     */
    public static function crc62($source)
    {
        if (!is_string($source)) {
            return false;
        }
        $int = \abs(\crc32($source));
        return \gmp_strval(\gmp_init($int, 10), 62);
    }

    /**
     * Combine XML documents together. (Note this is NOT merging, when a
     * key_prefix is not provided key duplicates can occur!)
     *
     * Provide multiple documentInfo like:
     * array(
     *     'XML' => 'xml document here',
     *     'key_prefix' => 'some_prefix', // [optional] Will be prepended to every key
     * )
     *
     * @param  array $documentInfo Initial documentInfo to merge.
     * @param  array $_            [optional] Variable list of $documentInfo to combine.
     *
     * @return array Like:
     * array(
     *     'XML' => 'the combined xml document',
     *     'key_prefixes' => array( // [optional] when key prefixes have been used
     *         'some_prefix',
     *         'other_prefix',
     *         ...
     *     ),
     * );
     */
    public static function combineXMLDocuments($documentInfo, $_ = null)
    {
        $documentInfos = func_get_args();
        array_shift($documentInfos);
        if (empty($documentInfos)) {
            return $documentInfo;
        }

        $newline = static::NL_NIX;

        $return = static::shiftXMLDocumentStart(
            $documentInfo['XML'],
            $newline
        );

        $documentEnd = static::popXMLDocumentEnd(
            $documentInfo['XML'],
            $newline
        );

        $addPrefix = function(&$documentInfo) {
            if (
                null !== (
                    $prefix = ArrayUtils::arrayTarget('key_prefix', $documentInfo)
                )
            ) {
                $documentInfo['XML'] = static::prefixKeysOnXMLDocument(
                    $documentInfo['XML'],
                    $prefix
                );
            }
            return $documentInfo;
        };

        $addPrefix($documentInfo);
        $return .= $documentInfo['XML'];

        foreach ($documentInfos as $extraInfo) {
            static::shiftXMLDocumentStart(
                $extraInfo['XML'],
                $newline
            );
            static::popXMLDocumentEnd(
                $extraInfo['XML'],
                $newline
            );
            $addPrefix($extraInfo);
            $return .= $extraInfo['XML'];
        }
        return $return . $documentEnd;
    }

    /**
     * Detect the newline type on a string
     *
     * @param string $string
     *
     * @return string
     */
    public static function detectNewlineType($string)
    {
        if (strpos($string, static::NL_WIN) !== false){
            return static::NL_WIN;
        } elseif (strpos($string, static::NL_MAC) !== false) {
            return static::NL_MAC;
        } elseif (strpos($string, static::NL_NIX) !== false) {
            return static::NL_NIX;
        }
        // When the string does not have new lines return the NIX variant
        return static::NL_NIX;
    }

    /**
     * Convert newline characters of a string
     *
     * @param string $string
     * @param string $newline
     *
     * @return string
     */
    public static function convertNewline($string, $newline = null)
    {
        $newline = $newline ?: static::detectNewlineType($string);
        return str_replace(
            array(
                static::NL_WIN,
                static::NL_MAC,
                static::NL_NIX
            ),
            $newline,
            $string
        );
    }

    /**
     * Shift document start off the beginning of XML string
     *
     * @param string $document
     * @param string $newline
     *
     * @return string the shifted document start, or NULL if we can't find it
     */
    public static function shiftXMLDocumentStart(&$document, $newline = null)
    {
        $newline = $newline ?: static::detectNewlineType($document);
        if (preg_match(
            '/<[a-zA-Z0-9\-_]+\:[a-zA-Z0-9\-_\s\=\"\']+?>(.+)?' . $newline . '/m',
            $document,
            $matches
        )) {
            $document = preg_replace(
                '/<[a-zA-Z0-9\-_]+\:[a-zA-Z0-9\-_\s\=\"\']+?>(.+)?' . $newline . '/m',
                '',
                $document,
                1
            );
            return $matches[0];
        }
        return null;
    }

    /**
     * Pop document end off the end of XML string
     *
     * @param string $document
     * @param string $newline
     *
     * @return string the popped document end, or NULL if we can't find it
     */
    public static function popXMLDocumentEnd(&$document, $newline = null)
    {
        $newline = $newline ?: static::detectNewlineType($document);
        if (preg_match(
            '/^<\/[a-zA-Z0-9\-_]+\:[a-zA-Z0-9\-_\s\=\"\']+?>(.+)?(' . $newline . ')?/m',
            $document,
            $matches
        )) {
            $document = preg_replace(
                '/^<\/[a-zA-Z0-9\-_]+\:[a-zA-Z0-9\-_\s\=\"\']+?>(.+)?(' . $newline . ')?/m',
                '',
                $document,
                1
            );
            return $matches[0];
        }
        return null;
    }

    /**
     * Adds a prefix to all keys within the XML document
     *
     * @param string $document
     * @param string $prefix
     * @return string
     */
    public static function prefixKeysOnXMLDocument($document, $prefix)
    {
        return preg_replace(
            '/<(\/)?([a-zA-Z0-9\-_]+)(\:[a-zA-Z0-9\-_\s\=\"\']+)?>/',
            '<$1' . $prefix . '_$2$3>',
            $document
        );
    }

    /**
     * Parse a domain or host or full url to get all necessary information
     *
     * @param string $source uri or host/domain
     * @param string $needle When provided only the matching key from the
     *                       full result will be returned.
     *
     * @return string|array sample: (Only info found will get returned)
     * When a $needle is provided the matched item in the array gets
     * returned (string|array) or null when not available
     * array(11) {
     *   ["scheme"]        => "http"
     *   ["authority"]     => "user:pass@sub.level.nested.domain.com"
     *   ["userinfo"]      => "user:pass"
     *   ["username"]      => "user"
     *   ["password"]      => "pass"
     *   ["host"]          => "sub.level.nested.domain.com"
     *   ["domain"]        => "sub.level.nested.domain.com"
     *   ["ip"]            => "123.123.123.123"
     *   ["port"]          => "123456"
     *   ["path"]          => "/where/to/go"
     *   ["query"]         => "bla=1"
     *   ["domain_levels"] => array(5) {
     *                          [0] => "com"
     *                          [1] => "domain.com"
     *                          [2] => "nested.domain.com"
     *                          [3] => "level.nested.domain.com"
     *                          [4] => "sub.level.nested.domain.com"
     *                        }
     *   ["sub_domains"]   => "sub.level.nested"
     *   ["label"]         => "domain"
     *   ["tld"]           => "com"
     * }
     */
    public static function parseHost($source, $needle = null)
    {
        static $runtimeCache = array();

        if (array_key_exists($source, $runtimeCache)) {
            return $runtimeCache[$source];
        }

        $return = array();
        preg_match("~^(?:(?:(?P<scheme>[a-z][0-9a-z.+-]*?)://)?(?P<authority>(?:(?P<userinfo>(?P<username>(?:[\w.\~-]|(?:%[\da-f]{2})|[!$&'()*+,;=])*)?:(?P<password>(?:[\w.\~-]|(?:%[\da-f]{2})|[!$&'()*+,;=])*)?|(?:[\w.\~-]|(?:%[\da-f]{2})|[!$&'()*+,;=]|:)*?)@)?(?P<host>(?P<domain>(?:[a-z](?:[0-9a-z-]*(?:[0-9a-z]))?\.)+(?:[a-z](?:[0-9a-z-]*(?:[0-9a-z]))?))|(?P<ip>(?:25[0-5]|2[0-4]\d|[01]\d\d|\d?\d).(?:25[0-5]|2[0-4]\d|[01]\d\d|\d?\d).(?:25[0-5]|2[0-4]\d|[01]\d\d|\d?\d).(?:25[0-5]|2[0-4]\d|[01]\d\d|\d?\d)))(?::(?P<port>\d+))?(?=/|$)))?(?P<path>/?(?:(?:[\w.\~-]|(?:%[\da-f]{2})|[!$&'()*+,;=]|:|@)+/)*(?:(?:[\w.\~-]|(?:%[\da-f]{2})|[!$&'()*+,;=]|:|@)+/?)?)(?:\?(?P<query>(?:(?:[\w.\~-]|(?:%[\da-f]{2})|[!$&'()*+,;=]|:|@)|/|\?)*?))?(?:#(?P<fragment>(?:(?:[\w.\~-]|(?:%[\da-f]{2})|[!$&'()*+,;=]|:|@)|/|\?)*))?$~i", $source, $matches);
        foreach ($matches as $key => $value) {
            if (is_string($key) && !empty($key) && !empty($value)) {
                $return[$key] = $value;
            }
        }
        if (isset($return['host'])) {
            // tld e.g. .com
            $parts = explode('.', $return['host']);
            $parts = array_reverse($parts);
            $return['domain_levels'] = array();
            $append = '';
            foreach ($parts as $part) {
                $return['domain_levels'][] = $part.$append;
                $append = '.' . $part.$append;
            }
            $subDomains = $return['host'];
            $domain     = $return['domain_levels'][1];
            $subDomains = rtrim(strstr($subDomains, $domain, true), '.');
            $return['sub_domains'] = $subDomains;
            if (isset($parts[1])) {
                $return['label'] = $parts[1];
            }
            if (isset($parts[0])) {
                $return['tld'] = $parts[0];
            }
        }

        $runtimeCache[$source] = $return;
        if (null !== $needle) {
            return ArrayUtils::arrayTarget($needle, $return);
        }
        
        return $return;
    }
}
