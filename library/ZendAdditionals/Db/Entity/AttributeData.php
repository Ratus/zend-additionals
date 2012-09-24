<?php

namespace ZendAdditionals\Db\Entity;

class AttributeData
{
    protected $entityId;
    protected $attributeId;
    protected $attributePropertyId;
    protected $value;
    protected $valueTmp;

    public function getEntityId()
    {
        return $this->entityId;
    }

    public function setEntityId($entityId)
    {
        $this->entityId = $entityId;
        return $this;
    }

    public function getAttributeId()
    {
        return $this->attributeId;
    }

    public function setAttributeId($attributeId)
    {
        $this->attributeId = $attributeId;
        return $this;
    }

    public function getAttributePropertyId()
    {
        return $this->attributePropertyId;
    }

    public function setAttributePropertyId($attributePropertyId)
    {
        $this->attributePropertyId = $attributePropertyId;
        return $this;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    public function getValueTmp()
    {
        return $this->valueTmp;
    }

    public function setValueTmp($valueTmp)
    {
        $this->valueTmp = $valueTmp;
        return $this;
    }
}

