<?php
namespace ZendAdditionals\Db\Entity;

class EventContainer
{
    const HYDRATE_TYPE_OBJECT = 'object',
          HYDRATE_TYPE_ARRAY  = 'array';

    /**
     * @var string
     */
    protected $entityClassName;

    /**
     * @var mixed
     */
    protected $data;

    /**
     * @var constant self::HYDRATE_TYPE_OBJECT or self::HYDRATE_TYPE_ARRAY
     */
    protected $hydrateType;

    /**
     * @param mixed $entityClassName
     *
     * @return EventContainer
     */
    public function setEntityClassName($entityClassName)
    {
        $this->entityClassName = $entityClassName;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getEntityClassName()
    {
        return $this->entityClassName;
    }

    /**
     * @param mixed $data
     *
     * @return EventContainer
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param mixed $hydrateType
     *
     * @return EventContainer;
     */
    public function setHydrateType($hydrateType)
    {
        $this->hydrateType = $hydrateType;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getHydrateType()
    {
        return $this->hydrateType;
    }
}
