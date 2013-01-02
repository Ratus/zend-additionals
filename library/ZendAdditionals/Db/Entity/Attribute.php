<?php
namespace ZendAdditionals\Db\Entity;

class Attribute
{
    protected $id;
    protected $label;
    protected $type;
    protected $length;
    protected $required;
    protected $moderationRequired;
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

    public function isRequired(){
        return (bool)$this->required;
    }

    public function setRequired($required){
        $this->required = (bool)$required;
        return $this;
    }

    public function isModerationRequired(){
        return (bool)$this->moderationRequired;
    }

    public function setModerationRequired($moderationRequired){
        $this->moderationRequired = (bool)$moderationRequired;
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

