<?php
namespace ZendAdditionals\Ssh;

use ZendAdditionals\Ssh\Connect;

/**
 * @category ZendAdditionals
 * @package Ssh
 */
class Ssh
{
    /**
     * @var string
     */
    protected $host;

    /**
     * @var integer
     */
    protected $port;

    /**
     * @var Connect\Methods|null
     */
    protected $connectMethods;

    /**
     * @var Connect\Callbacks|null
     */
    protected $connectCallbacks;

    /**
     * @var resource
     */
    protected $ssh2;

    /**
     * @var boolean
     */
    protected $isConnected = false;

    /**
     * @var boolean
     */
    protected $isAuthenticated = false;

    /**
     * @var boolean
     */
    protected $useCustomErrorHandler = false;

    public function __construct(
        $host,
        $port = 22,
        Connect\Methods $methods = null,
        Connect\Callbacks $callbacks = null
    ) {
        $this->host             = $host;
        $this->port             = $port;

        $this->connectMethods   = $methods ?: new Connect\Methods();
        $this->connectCallbacks = $callbacks ?: new Connect\Callbacks();
    }

    /**
     * Connect to the SSH server when
     * connection not has been established yet
     *
     * @return void
     */
    protected function checkConnected()
    {
        /**
         * Connect to the ssh server
         * when no connection has been established
         */
        if ($this->isConnected === false) {
            $this->connect();
        }
    }

    /**
     * Check if we are authenticated
     *
     * @return void
     * @throws Exception\RuntimeException
     */
    protected function isAuthenticated()
    {
        // Check connection
        $this->checkConnected();

        // Check if the user has called am authentication method
        if ($this->isAuthenticated === false) {
            $class = __CLASS__;
            throw new Exception\RuntimeException(
                "Not authenticated yet. Call {$class}::authenticateByKey() or ".
                "{$class}::authenticateByCrendentials() first."
            );
        }
    }

    /**
     * Validates the width/height type given by exec and/or shell
     *
     * @param int $widthHeightType
     *
     * @return void
     * @throws Exception\InvalidArgumentException
     */
    protected function validateWidthHeightType($widthHeightType)
    {
        if ($widthHeightType !== SSH2_TERM_UNIT_CHARS &&
            $widthHeightType !== SSH2_TERM_UNIT_PIXELS
        ) {
            throw new Exception\InvalidArgumentException(
                '$widthHeightType can only be SSH2_TERM_UNIT_CHARS or SSH2_TERM_UNIT_PIXELS'
            );
        }
    }

    /**
     * Connect to the SSH server
     *
     * @return void
     */
    protected function connect()
    {
        $this->ssh2 = ssh2_connect(
            $this->host,
            $this->port,
            $this->connectMethods->toArray(),
            $this->connectCallbacks->toArray()
        );

        // Check if the connection was a success
        if ($this->ssh2 === false) {
            throw new Exception\UnexpectedValueException(
                "Could not connect to ssh://{$this->host}:{$this->port}"
            );
        }

        $this->isConnected = true;
    }

    /**
     * Set custom error handler
     * This come handy when executing php function and you want to catch the
     * php errors instead of supressing them
     *
     * @return void
     */
    protected function useCustomErrorHandler()
    {
        set_error_handler(function(
            $errNo,
            $errStr,
            $errFile = null,
            $errLine = 0,
            $errContext = array()
        ) {
            throw new Exception\RuntimeException(
                $errStr,
                $errNo
            );
        });

        $this->useCustomErrorHandler = true;
    }

    /**
     * Release the custom error handler when used
     *
     * @return void
     */
    protected function releaseCustomErrorHandler()
    {
        if ($this->useCustomErrorHandler) {
            restore_error_handler();
            $this->useCustomErrorHandler = false;
        }
    }

    /**
     * Authenticate with username/password
     *
     * @param string $username
     * @param string $password
     *
     * @return void
     * @throws Exception\RuntimeException
     */
    public function authenticateByCredentials($username, $password)
    {
        // Check connection
        $this->checkConnected();

        // Catch any errors thrown by ssh2 lib
        $this->useCustomErrorHandler();

        $result = ssh2_auth_password($this->ssh2, $username, $password);

        $this->releaseCustomErrorHandler();

        if ($result === false) {
            throw new Exception\RuntimeException(
                "Authentication by credentials failed. Invalid username/password"
            );
        }

        $this->isAuthenticated = true;
    }

    /**
     * Authenticate with username/keys
     *
     * @param string $username
     * @param string $publicKey     The location of the public key
     * @param string $privateKey    The location of the private key
     * @param string $passphrase    The password of the private key
     *
     * @return void
     * @throws Exception\RuntimeException
     */
    public function authenticateByKey($username, $publicKey, $privateKey, $passphrase = null)
    {
        // Check connection
        $this->checkConnected();

        // Check readability of the file and if the file exists
        if (is_readable($publicKey) === false) {
            throw new Exception\UnexpectedValueException(
                "Could not read public key: {$publicKey}"
            );
        }

        // Check readability of the file and if the file exists
        if (is_readable($privateKey) === false) {
            throw new Exception\UnexpectedValueException(
                "Could not read private key: {$privateKey}"
            );
        }

        // Catch any errors thrown by ssh2 lib
        $this->useCustomErrorHandler();

        // Perform authentication
        $result = ssh2_auth_pubkey_file(
            $this->ssh2,
            $username,
            $publicKey,
            $privateKey,
            $passphrase
        );

        $this->releaseCustomErrorHandler();

        if ($result === false) {
            throw new Exception\RuntimeException(
                "Authentication by keys failed."
            );
        }

        $this->isAuthenticated = true;
    }

    /**
     * Execute a command the the SSH server
     *
     * @param string    $command
     * @param resource  $ioStream
     * @param mixed     $ioStreamBlocking
     * @param resource  $errStream
     * @param mixed     $errStreamBlocking
     * @param string    $pty                ("vt102", "ansi", etc)
     * @param array     $env
     * @param int       $width
     * @param int       $height
     * @param int       $widthHeightType
     */
    public function exec(
        $command,
        &$ioStream,
        $ioStreamBlocking   = true,
        &$errStream         = null,
        $errStreamBlocking  = true,
        $pty                = null,
        array $env          = null,
        $width              = 80,
        $height             = 25,
        $widthHeightType    = SSH2_TERM_UNIT_CHARS
    ){
        // Check parameter input (width/height type)
        $this->validateWidthHeightType($widthHeightType);

        // Check connection and authentication
        $this->isAuthenticated();

        $ioStream = ssh2_exec(
            $this->ssh2,
            $command,
            $pty,
            $env,
            $width,
            $height,
            $widthHeightType
        );

        // Fetch the error stream
        $errStream = ssh2_fetch_stream($ioStream, SSH2_STREAM_STDERR);

        // Set blocking modes
        stream_set_blocking($ioStream, $ioStreamBlocking);
        stream_set_blocking($errStream, $errStreamBlocking);
    }

    /**
     * Returns a server hostkey hash from an active session.
     *
     * @param int $flags  SSH2_FINGERPRINT_MD5 or SSH2_FINGERPRINT_SHA1
     *
     * @return stringThe fingerprint
     */
    public function fingerprint($flags = 0)
    {
        // Check connection and authentication
        $this->isAuthenticated();

        return ssh2_fingerprint($this->ssh2, $flags);
    }

    /**
     * Creates an shell
     *
     * @param resource $ioStream        Standard io stream
     * @param mixed $ioStreamBlocking   Stream blockingmode
     * @param resource $errStream       Standard Error stream
     * @param mixed $errStreamBlocking  Stream blocking mode
     * @param mixed $termType           The terminal type
     * @param mixed $env                Environment variables as key/value pair
     * @param mixed $width              The width of the virtual terminal
     * @param mixed $height             The height of the virtual terminal
     * @param mixed $widthHeightType    The width/height type of the virtual terminal
     *
     * @return void
     */
    public function shell(
        &$ioStream,
        $ioStreamBlocking   = true,
        &$errStream         = null,
        $errStreamBlocking  = true,
        $termType           = 'vanilla',
        $env                = array(),
        $width              = 80,
        $height             = 25,
        $widthHeightType    = SSH2_TERM_UNIT_CHARS
    ) {
        // Check parameter input
        $this->validateWidthHeightType($widthHeightType);

        // Check connection and authentication
        $this->isAuthenticated();

        // Fetch the main stream
        $ioStream = ssh2_shell(
            $this->ssh2,
            $termType,
            $env,
            $width,
            $height,
            $widthHeightType
        );

        // Fetch the error stream
        $errStream = ssh2_fetch_stream($ioStream, SSH2_STREAM_STDERR);

        // Set blocking modes
        stream_set_blocking($ioStream, $ioStreamBlocking);
        stream_set_blocking($errStream, $errStreamBlocking);
    }
}
