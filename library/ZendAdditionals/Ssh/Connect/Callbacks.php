<?php
namespace ZendAdditionals\Ssh\Connect;

use ZendAdditionals\Ssh\Exception;

/**
 * @category    ZendAdditionals
 * @package     Ssh
 * @subpackage  Connect
 */
class Callbacks
{
    /**
     * @var callback
     */
    protected $ignore;

    /**
     * @var callback
     */
    protected $debug;

    /**
     * @var callback
     */
    protected $macerror;

    /**
     * @var callback
     */
    protected $disconnect;

    /**
     * Function to call when an SSH2_MSG_IGNORE packet is received
     *
     * @param callback $ignore
     *
     * @return ConnectCallbacks
     */
    public function setIgnore($ignore)
    {
        if (is_callable($ignore) === false) {
            throw new Exception\InvalidArgumentException(
                '$ignore is not callable!'
            );
        }

        $this->ignore = $ignore;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getIgnore()
    {
        return $this->ignore;
    }

    /**
     * Function to call when an SSH2_MSG_DEBUG packet is received
     *
     * @param callback $debug
     *
     * @return ConnectCallbacks
     * @throws Exception\InvalidArgumentException
     */
    public function setDebug($debug)
    {
        if (is_callable($debug) === false) {
            throw new Exception\InvalidArgumentException(
                '$debug is not callable!'
            );
        }

        $this->debug = $debug;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDebug()
    {
        return $this->debug;
    }

    /**
     * Function to call when a packet is received but the
     *     message authentication code failed.
     * If the callback returns TRUE, the mismatch will be ignored,
     *     otherwise the connection will be terminated.
     *
     * @param callback $macerror
     *
     * @return ConnectCallbacks
     * @throws Exception\InvalidArgumentException
     */
    public function setMacerror($macerror)
    {
        if (is_callable($macerror) === false) {
            throw new Exception\InvalidArgumentException(
                '$macerror is not callable!'
            );
        }

        $this->macerror = $macerror;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getMacerror()
    {
        return $this->macerror;
    }

    /**
     * Function to call when an SSH2_MSG_DISCONNECT packet is received
     *
     * @param callback $disconnect
     *
     * @return ConnectCallbacks
     * @throws Exception\InvalidArgumentException
     */
    public function setDisconnect($disconnect)
    {
        if (is_callable($disconnect) === false) {
            throw new Exception\InvalidArgumentException(
                '$disconnect is not callable!'
            );
        }

        $this->disconnect = $disconnect;

        return $this;
    }

    /**
     * @return callback
     */
    public function getDisconnect()
    {
        return $this->disconnect;
    }

    /**
     * Creates an associative array for ssh_connect
     *
     * @return array
     */
    public function toArray()
    {
        $return = array();

        $ignore     = $this->getIgnore();
        $debug      = $this->getDebug();
        $macError   = $this->getMacerror();
        $disconnect = $this->getDisconnect();

        if (empty($ignore) === false) {
            $retur['ignore'] = $ignore;
        }

        if (empty($debug) === false) {
            $return['debug'] = $debug;
        }

        if (empty($macError) === false) {
            $return['macerror'] = $macError;
        }

        if (empty($disconnect) === false) {
            $return['disconnect'] = $disconnect;
        }

        return $return;
    }
}

