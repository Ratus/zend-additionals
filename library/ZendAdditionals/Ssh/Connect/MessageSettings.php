<?php
namespace ZendAdditionals\Ssh\Connect;

use ZendAdditionals\Ssh\Exception;

/**
 * @category    ZendAdditionals
 * @package     Ssh
 * @subpackage  Connect
 */
class MessageSettings
{
    const CRYPT_RIJNDAEL_CBC    = 'rijndael-cbc@lysator.liu.se',
          CRYPT_AES256_CBC      = 'aes256-cbc',
          CRYPT_AES192_CBC      = 'aes192-cbc',
          CRYPT_3DES_CBC        = 'cbc3des-cbc',
          CRYPT_BLOWFISH_CBC    = 'blowfish-cbc',
          CRYPT_CAST128_CBC     = 'cast128-cbc',
          CRYPT_ARCFOUR         = 'arcfour',
          CRYPT_NONE            = 'none';

    const COMPRESSION_ZLIB      = 'zlib',
          COMPRESSION_NONE      = 'none';

    const MAC_HMAC_SHA1                             = 'hmac-sha1',
          MAC_HMAC_SHA1_96                          = 'hmac-sha1-96',
          MAC_HMAC_RIPEMD160                        = 'hmac-ripemd160',
          MAC_HMAC_RIPEMD160_AT_OPPENSSH_DOT_COM    = 'hmac-ripemd160@openssh.com',
          MAC_NONE                                  = 'none';

    /**
     * Available Crypro methods
     *
     * @var array
     */
    protected $availableCryptoMethods = array(
        self::CRYPT_3DES_CBC,
        self::CRYPT_AES192_CBC,
        self::CRYPT_AES256_CBC,
        self::CRYPT_ARCFOUR,
        self::CRYPT_BLOWFISH_CBC,
        self::CRYPT_CAST128_CBC,
        self::CRYPT_NONE,
        self::CRYPT_RIJNDAEL_CBC,
    );

    /**
     * Available compression methods
     *
     * @var array
     */
    protected $availableCompressionMethods = array(
        self::COMPRESSION_NONE,
        self::COMPRESSION_ZLIB,
    );

    /**
     * Available Message Authentication Code Methods
     *
     * @var array
     */
    protected $availableMacMethods = array(
        self::MAC_HMAC_RIPEMD160,
        self::MAC_HMAC_RIPEMD160_AT_OPPENSSH_DOT_COM,
        self::MAC_HMAC_SHA1,
        self::MAC_HMAC_SHA1_96,
        self::MAC_NONE,
    );

    /**
     * List of crypto methods to advertise
     *
     * @var array
     */
    protected $cryptoMethods;

    /**
     * List of compression methods to advertise
     *
     * @var array
     */
    protected $compressionMethods;

    /**
     * List of MAC methods to advertise
     *
     * @var array
     */
    protected $macMethods;


    /**
     * List of crypto methods to advertise
     *
     * @param array $crypt
     *
     * @return ConnectMessageSettings
     * @throws Exception\InvalidArgumentException
     */
    public function setCryptoMethods(array $crypt)
    {
        $diff = array_diff($crypt, $this->availableCryptoMethods);

        if (count($diff) > 0) {
            throw new Exception\InvalidArgumentException(
                "Crypt method(s) '".implode(', ', $diff)."' does not exists"
            );
        }

        $this->cryptoMethods = $crypt;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getCryptoMethods()
    {
        return $this->cryptoMethods;
    }

    /**
     * @param array $comp
     *
     * @return ConnectMessageSettings
     * @throws Exception\InvalidArgumentException
     */
    public function setCompressionMethods(array $compressionMethods)
    {
        $diff = srray_diff($compressionMethods, $this->availableCompressionMethods);

        if (count($diff) > 0) {
            throw new Exception\InvalidArgumentException(
                "Compressionmethod(s) '".implode(', ', $diff)."' does not exists"
            );
        }

        $this->compressionMethods = $compressionMethods;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getCompressionMethods()
    {
        return $this->compressionMethods;
    }

    /**
     * @param array $mac
     *
     * @return ConnectMessageSettings
     * @throws Exception\InvalidArgumentException
     */
    public function setMacMethods(array $macMethods)
    {
        $diff = array_diff($macMethods, $this->availableMacMethods);

        if (count($diff) > 0) {
            throw new Exception\InvalidArgumentException(
                "MAC method(s) '".implode(', ', $diff)."' does not exissts"
            );
        }

        $this->macMethods = $macMethods;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getMacMethods()
    {
        return $this->macMethods;
    }

    /**
     * Return an associative array with settings for ssh_connect
     *
     * @return array
     */
    public function toArray()
    {
        $return = array();

        $crypt  = $this->getCryptoMethods();
        $comp   = $this->getCompressionMethods();
        $mac    = $this->getMacMethods();

        if (empty($crypt) === false) {
            $return['crypt'] = $crypt;
        }

        if (empty($comp) === false) {
            $return['comp'] = $comp;
        }

        if(empty($mac) === false) {
            $return['mac'] = $mac;
        }

        return $return;
    }
}
