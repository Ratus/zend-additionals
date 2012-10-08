<?php
namespace ZendAdditionals\Db\Mapper;

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
}

