<?php
namespace ZendAdditionals\Stdlib;

use ZendAdditionals\Stdlib\ArrayUtils;

class StringUtils extends \Zend\Stdlib\StringUtils
{
    const NL_NIX = "\n";
    const NL_WIN = "\r\n";
    const NL_MAC = "\r";

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
    public function crc62($source)
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
            '<$1' . $prefix . '.$2$3>',
            $document
        );
    }
}
