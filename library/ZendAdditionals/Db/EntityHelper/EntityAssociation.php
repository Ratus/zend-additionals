<?php

namespace ZendAdditionals\Db\EntityHelper;

use DatingProfile\Mapper\AbstractMapper;
use ZendAdditionals\Stdlib\Hydrator\ObservableClassMethods;

class EntityAssociation
{
    protected $alias;
    protected $table;
    protected $prototype;
    protected $mapperServiceName;
    protected $joinCondition;
    protected $identifierColumn;
    protected $foreignColumn;
    protected $required;
    protected $requiredByAssociation;
    protected $associationRequiredRelation;

    protected $serviceManager;

    /**
     * Instantiate a new EntityAssociation, entity associations are used in mappers for entities that contain
     * associated entities.
     *
     * @param string $alias The alias name for this entity in the base entity
     * @param string $table In which table does this associated entity reside?
     * @param object $prototype Entity prototype used by hydration
     * @param string $mapperServiceName Mapper to use for loading this entity
     * @param string $joinCondition
     * @param string $identifierColumn The column that will be updated with the value of foreignColumn
     * @param string $foreignColumn The entity column which value will be used to store in identifierColumn
     * @param boolean $required Is this entity required?
     * @param boolean $requiredByAssociation Does the associated entity require the base entity?
     * @param array $associationRequiredRelation If the associated entity requires the base entity define
     * the relationship pointing back in an array like:
     * array('id' => 'profile_id') (base->id related with associated->profile_id)
     */
    public function __construct(
        $alias,
        $table,
        $prototype,
        $mapperServiceName,
        $joinCondition,
        $identifierColumn,
        $foreignColumn,
        $required = false,
        $requiredByAssociation = false,
        array $associationRequiredRelation = null
    ) {
        if (!is_object($prototype)) {
            throw new \InvalidArgumentException('Prototype must be an instance of an Entity');
        }
        $this->alias                       = $alias;
        $this->table                       = $table;
        $this->prototype                   = $prototype;
        $this->mapperServiceName           = $mapperServiceName;
        $this->joinCondition               = $joinCondition;
        $this->identifierColumn            = $identifierColumn;
        $this->foreignColumn               = $foreignColumn;
        $this->required                    = $required;
        $this->requiredByAssociation       = $requiredByAssociation;
        $this->associationRequiredRelation = $associationRequiredRelation;
    }

    public function setServiceManager($serviceManager)
    {
        $this->serviceManager = $serviceManager;
    }

    public function getServiceManager()
    {
        return $this->serviceManager;
    }

    public function getAlias()
    {
        return $this->alias;
    }

    public function getTable()
    {
        return $this->table;
    }

    /**
     * @return array (alias => table)
     */
    public function getjoinTable()
    {
        return array($this->getAlias() => $this->getTable());
    }

    public function getJoinCondition()
    {
        return $this->joinCondition;
    }

    public function getRequired()
    {
        return $this->required;
    }

    public function getRequiredByAssociation()
    {
        return $this->requiredByAssociation;
    }

    public function getAssociationRequiredRelation()
    {
        return $this->associationRequiredRelation;
    }

    /**
     *
     * @return array
     * @throws \UnexpectedValueException
     */
    public function getJoinColumns()
    {
        $hydrator = $this->getMapper()->getHydrator();
        if (!($hydrator instanceof ObservableClassMethods)) {
            throw new \UnexpectedValueException('EntityAssociation expects the mapper to have an ObservableClassMethods hydrator.');
        }
        $excludedColumns = $this->getMapper()->getEntityAssociationColumns();
        $columns = $hydrator->extract($this->prototype);
        foreach ($columns as $key => & $column) {
            if (
                array_search($key, $excludedColumns) !== false
            ) {
                unset($columns[$key]);
                continue;
            }
            $column = "{$this->alias}.{$key}";
        }
        return array_flip($columns);
    }

    public function getPrototype()
    {
        return $this->prototype;
    }

    public function getAssociatedEntity($baseEntity)
    {
        $formatter = new \Zend\Filter\Word\UnderscoreToCamelCase;
        if (!is_object($baseEntity)) {
            // throw
        }
        $getAssociatedEntityMethod = 'get' . $formatter($this->alias);
        if (!method_exists($baseEntity, $getAssociatedEntityMethod)) {
            // throw
        }
        return $baseEntity->$getAssociatedEntityMethod();
    }

    public function getCurrentAssociatedEntityId($baseEntity)
    {
        $formatter = new \Zend\Filter\Word\UnderscoreToCamelCase;
        if (!is_object($baseEntity)) {
            // throw
        }
        $getAssociacedEntityId = 'get' . $formatter($this->identifierColumn);
        if (!method_exists($baseEntity, $getAssociacedEntityId)) {
            // throw
        }
        return $baseEntity->$getAssociacedEntityId();
    }

    public function hasBaseEntity($associatedEntity)
    {
        $formatter = new \Zend\Filter\Word\UnderscoreToCamelCase;
        if (!is_object($associatedEntity)) {
            // throw
        }
        $relation = $this->getAssociationRequiredRelation();
        $associatedGetPointer = 'get' . $formatter(array_pop($relation));
        if (!method_exists($associatedEntity, $associatedGetPointer)) {
            // throw
        }
        $value = $associatedEntity->$associatedGetPointer();
        return !empty($value);
    }

    public function saveAssociatedEntity($associatedEntity)
    {
        $mapper = $this->getMapper();
        return $mapper->save($associatedEntity, false);
    }

    public function applyAssociatedEntityId($baseEntity, $associatedEntity)
    {
        $formatter = new \Zend\Filter\Word\UnderscoreToCamelCase;
        if (!is_object($baseEntity)) {
            // throw
        }
        if (!is_object($associatedEntity)) {
            // throw
        }
        $setAssociacedEntityId = 'set' . $formatter($this->identifierColumn);
        $getId = 'get' . $formatter($this->foreignColumn);
        if (!method_exists($associatedEntity, $getId)) {
            // throw
        }
        if (!method_exists($baseEntity, $setAssociacedEntityId)) {
            // throw
        }
        $baseEntity->$setAssociacedEntityId($associatedEntity->$getId());
    }

    public function getMapper()
    {
        $mapper = $this->getServiceManager()->get($this->mapperServiceName);
        if (!is_object($mapper) || !($mapper instanceof AbstractMapper)) {
            throw new \InvalidArgumentException('Mapper must be an instance of AbstractMapper');
        }
        return $mapper;
    }
}

