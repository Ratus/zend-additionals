<?php

namespace DatingProfile\Mapper;

use ZendAdditionals\Db\Mapper\AbstractMapper;
use DatingProfile\Db\Adapter\AdapterAwareInterface;

class AttributeMapper extends AbstractMapper implements AdapterAwareInterface
{
    protected $tableName = 'attributes';

    protected $autoGenerated = 'id';

    protected $primaries = array(
        array('id'),
    );
    protected $uniques = array(
        array('label'),
    );
}