<?php

namespace ZendAdditionals\Db\Mapper;

interface AdapterAwareInterface extends \Zend\Db\Adapter\AdapterAwareInterface
{
    public function setDbAdapter($adapter);
}