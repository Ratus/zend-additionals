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

        $this->getEventManager()->attach(
            'postSave',
            array($this, 'postSaveListener')
        );
    }

    /**
     * Post save listener to inject values on saved attribute data objects
     * when they are enum attributes. The values actually get unset during
     * preSave.
     */
    public function postSaveListener(Event $event)
    {
        $entity = $event->getParam('entity');
        if ($entity instanceof Entity\AttributeData) {
            if (
                $entity->getAttribute()->getType() === 'enum' &&
                null !== $entity->getAttributeProperty()
            ) {
                $entity->setValue(
                    $entity->getAttributeProperty()->getLabel()
                );
            }
        }
    }

    public function postInjectEntityListener(Event $event)
    {
        $object = $event->getParams();
        /** @var Entity\EventContainer */

        $data = $object->getData();
        if ($object->getEntityClassName() == 'ZendAdditionals\Db\Entity\AttributeData') {
            if ($object->getHydrateType() === Entity\EventContainer::HYDRATE_TYPE_OBJECT) {
                $propertyId = $data['entity']->getAttributePropertyId();
                if (!empty($propertyId)) {
                    $data['entity']->setValue($data['entity']->getAttributeProperty()->getLabel());
                }
            } else if (
                $object->getHydrateType() === Entity\EventContainer::HYDRATE_TYPE_ARRAY
            ) {
                $propertyId = $data['entity']['attribute_property_id'];
                if (!empty($propertyId)) {
                    $data['entity']['value'] = $data['entity']['attribute_property']['label'];
                }

                $object->setData($data);
            }
        }
    }

    /**
     * Checks the attributaData. Sets enumration id's.
     * Check for string lengths
     * Check if moderation is required
     *
     * @param Event $event
     * @triggers moderationRequired
     */
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

        // Fetch attribute based on parent relation information
        $attributeMapper = $this->getServiceManager()->get(Attribute::SERVICE_NAME);

        if (
            empty($parentRelationInfo) &&
            (
                null === $attribute ||
                $attributeMapper->isEntityEmpty($attribute)
            )
        ) {
            throw new \UnexpectedValueException(
                'When storing AttributeData the Attribute MUST be set.'
            );
        }

        if (
            !empty($parentRelationInfo) && (
                null === $attribute ||
                $attributeMapper->isEntityEmpty($attribute)
            )
        ) {
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

        if ($attribute->getType() === 'enum' && null === $value) {
            $entity->setAttributeProperty(null);
            $entity->setValue(null);
            $entity->setValueTmp(null);
        } elseif ($attribute->getType() === 'enum') {
            $attributePropertyMapper = $this->getServiceManager()->get(AttributeProperty::SERVICE_NAME);
            /** @var $attributePropertyMapper AttributeProperty */
            $properties = $attributePropertyMapper->getPropertiesByAttributeId($attribute->getId(), $tablePrefix);

            $propertyFound = false;
            foreach($properties as $property) {
                /** @var $property Entity\AttributeProperty */
                if ($value == $property->getLabel()) {
                    $propertyFound = true;
                    $entity->setAttributeProperty($property);
                    $entity->setValue(null);
                    $entity->setValueTmp(null);
                }
            }

            if ($propertyFound === false) {
                throw new Exception\UnexpectedValueException(
                    "Property '{$value}' does not exists for attribute '{$attribute->getLabel()}'"
                );
            }
        } else {
            if (strlen($value) > $attribute->getLength()) {
                throw new Exception\UnexpectedValueException(
                    'The value: ' . $value . ' is longer then ' . $attribute->getLength()
                );
            }

            if ($attribute->isRequired() && empty($value)) {
                throw new Exception\RuntimeException(
                    "The attribute is required, but has no value!"
                );
            }

            if ($attribute->isModerationRequired()) {
                if (self::$moderationMode) {
                    $entity->setValueTmp(null);
                    $entity->setValue($value);
                } else {
                    $entity->setValueTmp($value);
                    $entity->setValue(null);

                    // Inform other mappers about the moderation required
                    $this->getEventManager()->trigger('moderationRequired', $this, array(
                        'entity' => $entity
                    ));
                }
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

