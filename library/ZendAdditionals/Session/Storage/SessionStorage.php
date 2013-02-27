<?php
namespace ZendAdditionals\Session\Storage;

use ArrayObject;
use Zend\Session\Storage\ArrayStorage;

/**
 * Session storage in $_SESSION['__ZF2__']
 *
 * Replaces the $_SESSION['__ZF2__'] superglobal with an ArrayObject that allows for
 * property access, metadata storage, locking, and immutability.
 *
 * @category   ZendAdditionals
 * @package    Session
 * @subpackage Storage
 */
class SessionStorage extends ArrayStorage
{
    /**
     * Constructor
     *
     * Sets the $_SESSION['__ZF2__'] superglobal to an ArrayObject, maintaining previous
     * values if any discovered.
     *
     * @param  array|null $input
     * @param  int $flags
     * @param  string $iteratorClass
     */
    public function __construct(
        $input = null,
        $flags = ArrayObject::ARRAY_AS_PROPS,
        $iteratorClass = '\\ArrayIterator'
    ) {
        $resetSession = true;
        if ((null === $input) && isset($_SESSION)) {
            if (array_key_exists('__ZF2__', $_SESSION)) {
                $input = $_SESSION['__ZF2__'];
            } else {
                $input = array();
            }

            if (is_object($input) && $input instanceof \ArrayObject) {
                $resetSession = false;
            } elseif (is_object($input) && !$input instanceof \ArrayObject) {
                $input = (array) $input;
            }

        } elseif (null === $input) {
            $input = array();
        }

        parent::__construct($input, $flags, $iteratorClass);

        if ($resetSession) {
            $_SESSION['__ZF2__'] = $this;
        }
    }

    /**
     * Destructor
     *
     * Resets $_SESSION['__ZF2__'] superglobal to an array, by casting object using
     * getArrayCopy().
     *
     * @return void
     */
    public function __destruct()
    {
        $_SESSION['__ZF2__'] = (array) $this->getArrayCopy();
    }

    /**
     * Load session object from an existing array
     *
     * Ensures $_SESSION['__ZF2__'] is set to an instance of the object when complete.
     *
     * @param  array $array
     * @return SessionStorage
     */
    public function fromArray(array $array)
    {
        parent::fromArray($array);

        if (
            array_key_exists('__ZF2__', $_SESSION) === false ||
            $_SESSION['__ZF2__'] !== $this
        ) {
            $_SESSION['__ZF2__'] = $this;
        }

        return $this;
    }

    /**
     * Mark object as isImmutable
     *
     * @return SessionStorage
     */
    public function markImmutable()
    {
        $this['_IMMUTABLE'] = true;
        return $this;
    }

    /**
     * Determine if this object is isImmutable
     *
     * @return bool
     */
    public function isImmutable()
    {
        return (isset($this['_IMMUTABLE']) && $this['_IMMUTABLE']);
    }
}

