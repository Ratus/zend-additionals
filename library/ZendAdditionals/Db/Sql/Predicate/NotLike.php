<?php

namespace ZendAdditionals\Db\Sql\Predicate;

class NotLike extends \Zend\Db\Sql\Predicate\Like
{
    protected $specification = '%1$s NOT LIKE %2$s';
}

