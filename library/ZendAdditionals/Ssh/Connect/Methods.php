<?php
namespace ZendAdditionals\Ssh\Connect;

use ZendAdditionals\Ssh\Exception;

/**
 * @category    ZendAdditionals
 * @category    Ssh
 * @subpackage  Connect
 */
class Methods
{
    const KEX_DIFFIE_HELLMAN_GROUP1_SHA1         = 'diffie-hellman-group1-sha1',
          KEX_DIFFIE_HELLMAN_GROUP14_SHA1        = 'diffie-hellman-group14-sha1',
          KEX_DIFFIE_HELLMAN_GROUP_EXCHANGE_SHA1 = 'diffie-hellman-group-exchange-sha1';

    const HOSTKEY_SSH_RSA = 'ssh-rsa',
          HOSTKEY_SSH_DSS = 'ssh-dss';

    /**
     * List of key exchange methods to advertise
     *
     * @var array Each value will be self::KEX_* constant
     */
    protected $kex;

    /**
     * List of hostkey methods to advertise
     *
     * @var array each value will be a self::HOSTKEY_* constant
     */
    protected $hostkey;

    /**
     * Preferences for messages sent from client to server.
     *
     * @var ConnectMessageSettings
     */
    protected $clientToServer;

    /**
     * Preferences for messages sent from server to client.
     *
     * @var ConnectMessageSettings
     */
    protected $serverToClient;

    /**
     * @param mixed $kex
     *
     * @return ConnectMethods
     * @throws Exception\InvalidArgumentException
     */
    public function setKex(array $kex)
    {
        $availableKext = array(
            self::KEX_DIFFIE_HELLMAN_GROUP_EXCHANGE_SHA1,
            self::KEX_DIFFIE_HELLMAN_GROUP1_SHA1,
            self::KEX_DIFFIE_HELLMAN_GROUP14_SHA1,
        );

        $diff = array_diff($kex, $availableKext);

        if (count($diff) > 0) {
            throw new Exception\InvalidArgumentException(
                "Key exchange types '".implode(',', $diff)."' are not allowed"
            );
        }

        $this->kex = $kex;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getKex()
    {
        return $this->kex;
    }

    /**
     * @param mixed $hostkey
     *
     * @return ConnectMethods
     * @throws Exception\InvalidArgumentException
     */
    public function setHostkey(array $hostkey)
    {
        $validHostKeys = array(
            self::HOSTKEY_SSH_DSS,
            self::HOSTKEY_SSH_RSA,
        );

        $diff = array_diff($hostkey, $validHostKeys);

        if (count($diff) > 0) {
            throw new Exception\InvalidArgumentException(
                "Key types '".implode(',', $diff)."' are not allowed"
            );
        }

        $this->hostkey = $hostkey;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getHostkey()
    {
        return $this->hostkey;
    }

    /**
     * @param ConnectMessageSettings $clientToServer
     *
     * @return ConnectMethods|null
     */
    public function setClientToServer(ConnectMessageSettings $clientToServer)
    {
        $this->clientToServer = $clientToServer;
        return $this;
    }

    /**
     * @return ConnectMessageSettings
     */
    public function getClientToServer()
    {
        return $this->clientToServer;
    }

    /**
     * @param ConnectMessageSettings $serverToClient
     *
     * @return ConnectMethods|null
     */
    public function setServerToClient(ConnectMessageSettings $serverToClient)
    {
        $this->serverToClient = $serverToClient;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getServerToClient()
    {
        return $this->serverToClient;
    }

    /**
     * Get all settings for ssh_connect
     *
     * @return array
     */
    public function toArray()
    {
        $return = array();

        $kex            = $this->getKex();
        $hostkey        = $this->getHostkey();
        $clientToServer = $this->getClientToServer();
        $serverToClient = $this->getServerToClient();

        if (empty($kex) === false) {
            $return['kex'] = $kex;
        }

        if (empty($hostkey) === false) {
            $retur['hostkey'] = $hostkey;
        }

        if (empty($clientToServer) === false) {
            $return['client_to_server'] = $clientToServer->toArray();
        }

        if (empty($serverToClient) === false) {
            $return['server_to_client'] = $serverToClient->toArray();
        }

        return $return;
    }
}
