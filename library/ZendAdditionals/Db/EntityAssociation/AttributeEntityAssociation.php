<?php
namespace ZendAdditionals\Db\EntityAssociation;

use ZendAdditionals\Db\Mapper\AttributeMapper;
use ZendAdditionals\Db\Mapper\AttributePropertyMapper;
use ZendAdditionals\Db\Entity\Attribute;
use ZendAdditionals\Db\Entity\AttributeProperty;

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

    /**
    * @return AttributeMapper
    */
    public function getAttributeMapper()
    {
        return $this->getServiceManager()->get('attribute_mapper');
    }

    /**
    * @return AttributePropertyMapper
    */
    public function getAttributePropertyMapper()
    {
        return $this->getServiceManager()->get('attribute_property_mapper');
    }

    public function saveAssociatedEntity($associatedEntity)
    {
        $attributeMapper = $this->getAttributeMapper();

        if (null === $associatedEntity->getAttributeId()) {
            $attribute = $attributeMapper->getAttributeByLabel($this->getAttributePrefix(), $this->getAlias());

            if (false === $attribute) {
                throw new \UnexpectedValueException('Did not expect attribute id to be empty.');
            }

            $associatedEntity->setAttributeId($attribute->getId());
        } else {
            $attribute = $attributeMapper->getAttributeById(
                $this->getAttributePrefix(),
                $associatedEntity->getAttributeId()
            );
        }

		if (!$this->validateAttributeData($attribute, $associatedEntity)) {
			throw new \UnexpectedValueException('Yo dude, je input is niet geldig!');
		}

        if ($attribute->getType() === 'enum') {
            $attributePropertyMapper = $this->getAttributePropertyMapper();
            $propertyId = $attributePropertyMapper->getAttributePropertyIdByLabel(
                $this->getAttributePrefix(),
                $attribute->getId(),
                $associatedEntity->getValue()
            );

            if ($propertyId === FALSE) {
                throw new \UnexpectedValueException(
                    "Cannot find propertyId for attributeId {$attribute->getId()} AND label {$attribute->getLabel()}"
                );
            }

            $associatedEntity->setAttributePropertyId($propertyId);
            $associatedEntity->setValue(null);
            $associatedEntity->setValueTmp(null);
        }

        return parent::saveAssociatedEntity($associatedEntity);
    }

    public function validateAttributeData(Attribute $attribute, $associatedEntity)
    {
        $type = $attribute->getType();

        switch($type) {
            case 'enum':
                return $this->validateEnum($attribute, $associatedEntity);
            default:
                return $this->validateLength($attribute, $associatedEntity);
        }
    }

    protected function validateEnum(Attribute $attribute, $associatedEntity)
    {
        $attributePropertyMapper    = $this->getAttributePropertyMapper();

        $properties = $attributePropertyMapper->getAttributePropertiesForAttributeId(
            $this->getAttributePrefix(),
            $attribute->getId()
        );

        foreach($properties as $property) {
            /** @var AttributeProperty $property */
            if ($property->getLabel() === $associatedEntity->getValue()) {
                return true;
            }
        }

        return false;
    }

    protected function validateLength(Attribute $attribute, $associatedEntity)
    {
        $length = $attribute->getLength();
        $type   = $attribute->getType();
        $value  = $associatedEntity->getValue();

        if ($type === 'int') {
            return (bool) ((int) $associatedEntity->getValue() <= $length);
        }

        return (bool) (strlen($value) <= $length);
    }
}

