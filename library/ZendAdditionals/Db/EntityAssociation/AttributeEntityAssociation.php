<?php

namespace ZendAdditionals\Db\EntityAssociation;

class AttributeEntityAssociation extends EntityAssociation
{
    protected $attributePrefix;

    /**
     * @see EntityAssociation::__construct
     */
    public function __construct(
        $alias,
        $attributePrefix,
        $table,
        $prototype,
        $mapperServiceName,
        $joinCondition,
        $requiredByAssociation = false,
        array $associationRequiredRelation = null
    ) {
        $this->attributePrefix = $attributePrefix;
        parent::__construct($alias, $table, $prototype, $mapperServiceName, $joinCondition, null,
            null, false, $requiredByAssociation, $associationRequiredRelation);
    }

    public function getAttributePrefix()
    {
        return $this->attributePrefix;
    }

    public function getJoinColumns()
    {
        $columns = parent::getJoinColumns();
        $key = array_search('entity_id', $columns);
        if ($key === false) {
            throw new \Exception('argh!');
        }
        $columns[$key] = $this->getMappedEntityId();
        return $columns;
    }

    public function getCurrentAssociatedEntityId($baseEntity)
    {
        return null;
    }


    public function applyAssociatedEntityId($baseEntity, $associatedEntity)
    {
        return;
    }

    public function getMapper()
    {
        $mapper = parent::getMapper();


        $mappedEntityId = $this->getMappedEntityId();
        if (!empty($mappedEntityId)) {
            $mapper->addEntityColumnMap('entity_id', $mappedEntityId);
        }

        return $mapper;
    }

    public function getMappedEntityId()
    {
        reset($this->associationRequiredRelation);
        return current($this->associationRequiredRelation);
    }

    public function saveAssociatedEntity($associatedEntity)
    {
        if (null === $associatedEntity->getAttributeId()) {
            $attributeMapper = $this->getServiceManager()->get('attribute_mapper');
            $attributeId = $attributeMapper->getAttributeIdByLabel($this->getAttributePrefix(), $this->getAlias());
            if (false === $attributeId) {
                throw new \UnexpectedValueException('Did not expect attribute id to be empty.');
            }
            $associatedEntity->setAttributeId($attributeId);
        }
        
        return parent::saveAssociatedEntity($associatedEntity);
    }

}

