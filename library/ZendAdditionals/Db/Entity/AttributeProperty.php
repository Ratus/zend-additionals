<?php
namespace ZendAdditionals\Db\Entity;

/**
 * @category    ZendAdditionals
 * @package     Db
 * @subpackage  Entity
 */
class AttributeProperty extends \ZendAdditionals\Db\Entity\AbstractDbEntity
{
    /**
     * @var integer
     */
    protected $id;

    /**
     * @var integer
     */
    protected $attributeId;

    /**
     * @var string
     */
    protected $label;

    /**
     * @var string
     */
    protected $sortOrder;

    /**
     * @var Attribute
     */
    protected $attribute;

    /**
     * @return integer
     */
    public function getId(){
        return $this->id;
    }

    /**
     * @param integer $id
     * @return AttributeProperty
     */
    public function setId($id){
        $this->id = $id;
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
     * @param  Attribute $attribute
     * @return AttributeProperty
     */
    public function setAttribute(Attribute $attribute = null)
    {
        $this->attribute = $attribute;
        return $this;
    }

    /**
     * @return integer
     */
    public function getAttributeId(){
        return $this->attributeId;
    }

    /**
     * @param integer $attributeId
     * @return AttributeProperty
     */
    public function setAttributeId($attributeId){
        $this->attributeId = $attributeId;
        return $this;
    }

    /**
     * @return string
     */
    public function getLabel(){
        return $this->label;
    }

    /**
     * @param string $label
     * @return AttributeProperty
     */
    public function setLabel($label){
        $this->label = $label;
        return $this;
    }

    /**
     * @return integer
     */
    public function getSortOrder(){
        return $this->sortOrder;
    }

    /**
     * @param string $sortOrder
     * @return AttributeProperty
     */
    public function setSortOrder($sortOrder){
        $this->sortOrder = $sortOrder;
        return $this;
    }
}
