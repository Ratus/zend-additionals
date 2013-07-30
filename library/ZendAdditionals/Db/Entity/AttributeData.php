<?php
namespace ZendAdditionals\Db\Entity;

/**
 * @category    ZendAdditionals
 * @package     Db
 * @subpackage  Entity
 */
class AttributeData extends \ZendAdditionals\Db\Entity\AbstractDbEntity
{
    /**
     * @var integer
     */
    protected $entityId;

    /**
     * @var integer
     */
    protected $attributeId;

    /**
     * @var Attribute
     */
    protected $attribute;

    /**
     * @var integer
     */
    protected $attributePropertyId;

    /**
     * @var AttributeProperty
     */
    protected $attributeProperty;

    /**
     * @var string
     */
    protected $value;

    /**
     * @var string
     */
    protected $valueTmp;

    /**
     * @return integer
     */
    public function getEntityId()
    {
        return $this->entityId;
    }

    /**
     * @param integer $entityId
     * @return AttributeData
     */
    public function setEntityId($entityId)
    {
        $this->entityId = $entityId;
        return $this;
    }

    /**
     * @return integer
     */
    public function getAttributeId()
    {
        return $this->attributeId;
    }

    /**
     * @param integer $attributeId
     * @return AttributeData
     */
    public function setAttributeId($attributeId)
    {
        $this->attributeId = $attributeId;
        return $this;
    }

    /**
     * @return Attribute
     */
    public function getAttribute()
    {
        return $this->attribute;
    }

    /**
     * @param Attribute $attribute
     * @return AttributeData
     */
    public function setAttribute(Attribute $attribute)
    {
        $this->attribute = $attribute;
        return $this;
    }

    /**
     * @return integer
     */
    public function getAttributePropertyId()
    {
        return $this->attributePropertyId;
    }

    /**
     * @param integer $attributePropertyId
     * @return AttributeData
     */
    public function setAttributePropertyId($attributePropertyId = null)
    {
        $this->attributePropertyId = $attributePropertyId;
        return $this;
    }

    /**
     * @return AttributeProperty
     */
    public function getAttributeProperty()
    {
        return $this->attributeProperty;
    }

    /**
     * @param AttributeProperty $attributeProperty
     * @return AttributeData
     */
    public function setAttributeProperty(AttributeProperty $attributeProperty = null)
    {
        $this->attributeProperty = $attributeProperty;
        return $this;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     * @return AttributeData
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @return string
     */
    public function getValueTmp()
    {
        return $this->valueTmp;
    }

    /**
     * @param string $valueTmp
     * @return AttributeData
     */
    public function setValueTmp($valueTmp)
    {
        $this->valueTmp = $valueTmp;
        return $this;
    }
}
