<?php
namespace ZendAdditionals\Db\Entity;

/**
 * @category    ZendAdditionals
 * @package     Db
 * @subpackage  Entity
 */
class Attribute
{
    /**
     * @var integer
     */
    protected $id;

    /**
     * @var string
     */
    protected $label;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var integer
     */
    protected $length;

    /**
     * @var boolean
     */
    protected $required;

    /**
     * @var boolean
     */
    protected $moderationRequired;

    /**
     * @var string
     */
    protected $sortOrder;

    /**
     * @param integer $id
     * @return Attribute
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $label
     * @return Attribute
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $type
     * @return Attribute
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param integer $length
     * @return Attribute
     */
    public function setLength($length)
    {
        $this->length = $length;

        return $this;
    }

    /**
     * @return integer
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * @param boolean $required
     * @return Attribute
     */
    public function setRequired($required)
    {
        $this->required = $required;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getRequired()
    {
        return $this->required;
    }

    /**
     * @param boolean $moderationRequired
     * @return Attribute
     */
    public function setModerationRequired($moderationRequired)
    {
        $this->moderationRequired = $moderationRequired;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getModerationRequired()
    {
        return $this->moderationRequired;
    }

    /**
     * @param string $sortOrder
     * @return Attribute
     */
    public function setSortOrder($sortOrder)
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    /**
     * @return string
     */
    public function getSortOrder()
    {
        return $this->sortOrder;
    }
}
