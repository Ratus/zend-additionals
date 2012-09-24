<?php

namespace ZendAdditionals\Db\Entity;

class Attribute
{
    protected $id;
    protected $label;
    protected $type;
    protected $length;
    protected $isRequired;
    protected $isModerationRequired;
    protected $sortOrder;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function setLabel($label)
    {
        $this->label = $label;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getLength()
    {
        return $this->length;
    }

    public function setLength($length)
    {
        $this->length = $length;
    }

    public function getIsRequired()
    {
        return $this->isRequired;
    }

    public function setIsRequired($isRequired)
    {
        $this->isRequired = $isRequired;
    }

    public function getIsModerationRequired()
    {
        return $this->isModerationRequired;
    }

    public function setIsModerationRequired($isModerationRequired)
    {
        $this->isModerationRequired = $isModerationRequired;
    }

    public function getSortOrder()
    {
        return $this->sortOrder;
    }

    public function setSortOrder($sortOrder)
    {
        $this->sortOrder = $sortOrder;
    }
}

