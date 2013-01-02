<?php

namespace ZendAdditionals\Db\Sql\Predicate;

class NotBetween extends \Zend\Db\Sql\Predicate\Between
{
    protected $specification = '%1$s NOT BETWEEN %2$s AND %3$s';
}

