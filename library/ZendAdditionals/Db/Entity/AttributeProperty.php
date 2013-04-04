<?php
namespace ZendAdditionals\Db\Entity;

class AttributeProperty
{
    protected $id;
    protected $attributeId;
    protected $label;
    protected $sortOrder;

    /**
     * @var Attribute
     */
    protected $attribute;

    public function getId(){
        return $this->id;
    }

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

    public function getAttributeId(){
        return $this->attributeId;
    }

    public function setAttributeId($attributeId){
        $this->attributeId = $attributeId;
        return $this;
    }

    public function getLabel(){
        return $this->label;
    }

    public function setLabel($label){
        $this->label = $label;
        return $this;
    }

    public function getSortOrder(){
        return $this->sortOrder;
    }

    public function setSortOrder($sortOrder){
        $this->sortOrder = $sortOrder;
        return $this;
    }
}

