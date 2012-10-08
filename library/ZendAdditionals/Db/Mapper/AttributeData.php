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
        $this->getEventManager()->attach('postInjectEntity', function(Event $event) {
            $object = $event->getParam('entity');
            if ($object instanceOf Entity\AttributeData) {
                $propertyId = $object->getAttributePropertyId();
                if (!empty($propertyId)) {
                    $object->setValue($object->getAttributeProperty()->getLabel());
                }
            }
        });
    }

    /**
     *
     * @param \ZendAdditionals\Db\Entity\AttributeData $entity
     * @param type $tablePrefix
     * @param array $parentRelationInfo
     * @return type
     * @throws \UnexpectedValueException
     */
    public function save($entity, $tablePrefix = null, array $parentRelationInfo = null)
    {
        if (!($entity instanceof Entity\AttributeData)) {
            throw new \UnexpectedValueException(
                'The AttributeData mapper can only store AttributeData entities.'
            );
        }
        $attribute = $entity->getAttribute();
        if (
            empty($parentRelationInfo) && (
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

        $this->validateValue($entity, $tablePrefix);


        return parent::save($entity, $tablePrefix, $parentRelationInfo);
    }

    /**
     * Validate the value
     *
     * @param Entity\AttributeData $entity
     * @param string $tablePrefix
     */
    protected function validateValue(Entity\AttributeData $entity, $tablePrefix)
    {
        $changes = $this->getEntityChangesOnly($entity);
        if (empty($changes)) {
            return true;
        }
        $value = $entity->getValue();
        $attribute = $entity->getAttribute();
        if ($attribute->getType() === 'enum') {

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


        var_dump($changes);

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
}

