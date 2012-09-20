<?php

namespace ZendAdditionals\Db\Mapper;

interface ServiceManagerAwareInterface
{
    public function setServiceManager($serviceManager);

    public function getServiceManager();
}

