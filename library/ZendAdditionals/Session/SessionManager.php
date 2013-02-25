<?php
namespace ZendAdditionals\Session;

use Zend\Session\SaveHandler\SaveHandlerInterface;
use Zend\Session\Exception;

/**
 * Session ManagerInterface implementation utilizing ext/session
 * Note: Use a $_SESSION['__ZF2__'] for correct $_SESSION usage. Cannot attach object direct
 * to $_SESSION
 *
 * @category   ZendAdditionals
 * @package    Session
 */
class SessionManager extends \Zend\Session\SessionManager
{
    /**
     * Override the default storage class
     *
     * @var string
     */
    protected $defaultStorageClass = 'ZendAdditionals\Session\Storage\SessionStorage';

    /**
     * {@inheritdoc}
     */
    public function start($preserveStorage = false)
    {
        if ($this->sessionExists()) {
            return;
        }

        $saveHandler = $this->getSaveHandler();
        if ($saveHandler instanceof SaveHandlerInterface) {
            // register the session handler with ext/session
            $this->registerSaveHandler($saveHandler);
        }

        session_start();

        $storage = $this->getStorage();

        if (!$preserveStorage) {
            if (array_key_exists('__ZF2__', $_SESSION) === false) {
                $data = array();
            } else {
                $data = is_object($_SESSION['__ZF2__']) ? (array) $_SESSION['__ZF2__']->getArrayCopy() : array();
            }

            $storage->fromArray($data);
        }

        // Attach the storage to session variable
        $_SESSION['__ZF2__'] = $storage;

        if (!$this->isValid()) {
            throw new Exception\RuntimeException('Session validation failed');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function writeClose()
    {
        // The assumption is that we're using PHP's ext/session.
        // session_write_close() will actually overwrite $_SESSION with an
        // empty array on completion -- which leads to a mismatch between what
        // is in the storage object and $_SESSION. To get around this, we
        // temporarily reset $_SESSION to an array, and then re-link it to
        // the storage object.
        //
        // Additionally, while you _can_ write to $_SESSION following a
        // session_write_close() operation, no changes made to it will be
        // flushed to the session handler. As such, we now mark the storage
        // object isImmutable.
        $storage  = $this->getStorage();
        $_SESSION['__ZF2__'] = (array) $storage;
        session_write_close();
        $storage->fromArray($_SESSION['__ZF2__']);
        $storage->markImmutable();
    }
}

