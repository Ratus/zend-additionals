<?php
namespace ZendAdditionals\Db\Mapper;

use Zend\EventManager\Event;

use ZendAdditionals\Db\Entity;

class AttributeData extends AbstractMapper
{
    const SERVICE_NAME = 'ZendAdditionals\Db\Mapper\AttributeData';

    protected $tableName           = 'attribute_data';
    protected $tablePrefixRequired = true;

    protected $primaries = array(
        array('entity_id', 'attribute_id'),
    );

    protected $relations = array(
        'attribute' => array(
            'mapper_service_name'    => Attribute::SERVICE_NAME,
            'required'               => true,
            'recursive_table_prefix' => true,
            'reference'              => array('attribute_id' => 'id'),
        ),
        'attribute_property' => array(
            'mapper_service_name'    => AttributeProperty::SERVICE_NAME,
            'required'               => false,
            'recursive_table_prefix' => true,
            'reference'              => array('attribute_property_id' => 'id'),
        ),
    );

    public function __construct()
    {
        $this->getEventManager()->attach(
            'postInjectEntity',
            array($this, 'postInjectEntityListener')
        );

        $this->getEventManager()->attach(
            'preSave',
            array($this, 'preSaveListener')
        );
    }

    public function postInjectEntityListener(Event $event)
    {
        $object = $event->getParam('entity');
        if ($object instanceOf Entity\AttributeData) {
            $propertyId = $object->getAttributePropertyId();
            if (!empty($propertyId)) {
                $object->setValue($object->getAttributeProperty()->getLabel());
            }
        }
    }

    public function preSaveListener(Event $event)
    {
        $entity             = $event->getParam('entity');
        /** @var $entity Entity\AttributeData */

        $tablePrefix        = $event->getParam('table_prefix');
        $parentRelationInfo = $event->getParam('parent_relation_info');

       if (($entity instanceOf Entity\AttributeData) === false) {
           return;
       }


       $attribute = $entity->getAttribute();
       /** @var $attribute Entity\Attribute */

        if (
            empty($parentRelationInfo) &&
            (
                null === $attribute ||
                $this->isEntityEmpty($attribute)
            )
        ) {
            throw new \UnexpectedValueException(
                'When storing AttributeData the Attribute MUST be set.'
            );
        }

        if (
            !empty($parentRelationInfo) && (
                null === $attribute ||
                $this->isEntityEmpty($attribute)
            )
        ) {
            // Fetch attribute based on parent relation information
            $attributeMapper = $this->getServiceManager()->get(Attribute::SERVICE_NAME);
            $attribute = $attributeMapper->getAttributeByLabel(
                $parentRelationInfo['extra_conditions'][0]['right']['value'][0],
                $parentRelationInfo['extra_conditions'][0]['right']['value'][1]
            );
            $entity->setAttributeId($attribute->getId());
            $entity->setAttribute($attribute);
        }

        $changes = $this->getEntityChangesOnly($entity);
        if (empty($changes)) {
            return true;
        }

        $value = $entity->getValue();

        if ($attribute->getType() === 'enum') {
            $attributePropertyMapper = $this->getServiceManager()->get(AttributeProperty::SERVICE_NAME);
            /** @var $attributePropertyMapper AttributeProperty */
            $properties = $attributePropertyMapper->getPropertiesByAttributeId($attribute->getId(), $tablePrefix);

            $propertyFound = false;
            foreach($properties as $property) {
                /** @var $property Entity\AttributeProperty */
                if ($value === $property->getLabel()) {
                    $propertyFound = true;
                    $entity->setAttributeProperty($property);
                    $entity->setValue(null);
                    $entity->setValueTmp(null);
                }
            }

            if ($propertyFound === false) {
                throw new \UnexpectedValueException(
                    "Property '{$value}' does not exists for attribute '{$attribute->getLabel()}'"
                );
            }
        } else {
            // TODO: check datetime
            // TODO: check int
            // TODO: improve checks
            if (strlen($value) > $attribute->getLength()) {
                throw new \Exception('au');
            }
            if ($attribute->getIsRequired() && empty($value)) {
                throw new \Exception('au2');
            }
        }
    }

    protected function getEntityChangesOnly($entity)
    {
        $changes = $this->getHydrator()->extractChanges($entity);
        $columns = $this->getEntityAssociationColumns();
        foreach ($columns as $column) {
            if (isset($changes[$column])) {
                unset($changes[$column]);
            }
        }
        return $changes;
    }

    protected function getAllowFilters()
    {
        return true;
    }
}

