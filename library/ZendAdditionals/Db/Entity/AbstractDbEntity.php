<?php
namespace ZendAdditionals\Db\Entity;

/**
 * Basic entity logic
 *
 * @category    ZendAdditionals
 * @package     Db
 * @subpackage  Entity
 */
abstract class AbstractDbEntity
{
    /**
     * This method will clear the object of its content
     */
    public function __destruct()
    {
        // Fetch the properties of the object
        $vars = get_object_vars($this);
        $vars = array_keys($vars);

        foreach ($vars as $var) {
            // When object has been found, destruct it
            if (is_object($this->{$var})) {

                // Only when the method exists
                if (method_exists($this->{$var}, '__destruct')) {
                    $this->{$var}->__destruct();
                }
            }

            // Unset the property from the object
            $this->{$var} = null;
        }

        //clear local reference
        unset($vars);
    }
}
