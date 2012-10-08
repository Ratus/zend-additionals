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

    public function getId(){
        return $this->id;
    }

    public function setId($id){
        $this->id = $id;
        return $this;
    }

    public function getLabel(){
        return $this->label;
    }

    public function setLabel($label){
        $this->label = $label;
        return $this;
    }

    public function getType(){
        return $this->type;
    }

    public function setType($type){
        $this->type = $type;
        return $this;
    }

    public function getLength(){
        return $this->length;
    }

    public function setLength($length){
        $this->length = $length;
        return $this;
    }

    public function getIsRequired(){
        return $this->isRequired;
    }

    public function setIsRequired($isRequired){
        $this->isRequired = $isRequired;
        return $this;
    }

    public function getIsModerationRequired(){
        return $this->isModerationRequired;
    }

    public function setIsModerationRequired($isModerationRequired){
        $this->isModerationRequired = $isModerationRequired;
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

