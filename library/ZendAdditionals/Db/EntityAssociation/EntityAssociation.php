<?php

namespace ZendAdditionals\Db\EntityAssociation;

use ZendAdditionals\Db\Mapper\AbstractMapper;
use ZendAdditionals\Stdlib\Hydrator\ObservableClassMethods;

class EntityAssociation
{
    /**
     * @var string
     */
    protected $entityIdentifier;

    /**
     * @var string
     */
    protected $parentMapperServiceName;

    /**
     * @var string
     */
    protected $mapperServiceName;

    /**
     * @var \Zend\ServiceManager\ServiceManager
     */
    protected $serviceManager;

    /**
     * @var string
     */
    protected $generatedAlias;

    /**
     * @var int
     */
    protected static $aliasSuffixCount = 0;

    protected $parentAlias;

    protected $required;

    /**
     *
     * @param type $entityIdentifier
     * @param string $parentMapperServiceName
     * @param string $mapperServiceName
     */
    public function __construct(
        $entityIdentifier,
        $parentMapperServiceName,
        $mapperServiceName
    ) {
        static::$aliasSuffixCount++;
        $this->entityIdentifier        = $entityIdentifier;
        $this->parentMapperServiceName = $parentMapperServiceName;
        $this->mapperServiceName       = $mapperServiceName;
        $this->generatedAlias          = $entityIdentifier . '_' . static::$aliasSuffixCount;
    }

    public function getParentAlias()
    {
        return $this->parentAlias;
    }

    public function setParentAlias($parentAlias)
    {
        $this->parentAlias = $parentAlias;
        return $this;
    }


    public function getEntityIdentifier()
    {
        return $this->entityIdentifier;
    }

    public function setServiceManager($serviceManager)
    {
        $this->serviceManager = $serviceManager;
    }

    public function getServiceManager()
    {
        return $this->serviceManager;
    }

    public function getRequiredByAssociation()
    {
        return $this->requiredByAssociation;
    }

    public function getAssociationRequiredRelation()
    {
        return $this->associationRequiredRelation;
    }

    public function getAlias()
    {
        return $this->generatedAlias;
    }

    public function getTableName()
    {
        return $this->getMapper()->getTableName();
    }

    public function getJoinTable()
    {
        return array($this->getAlias() => $this->getTableName());
    }

    public function getJoinCondition()
    {

        $relation =  $this->getParentMapper()->getRelation($this->mapperServiceName, $this->entityIdentifier);
        return array(
            array(
                $this->getAlias() . '.' . $relation['foreign_id'],
                $relation['my_id'],
            )
        );
        // TODO add additional
    }

    public function getRelation()
    {
        return $this->getParentMapper()->getRelation($this->mapperServiceName, $this->entityIdentifier);
    }

    public function isBiDirectionalEntityReference($entityIdentifier)
    {
        $relation =  $this->getRelation();
        $subAssociations =  $this->getMapper()->getEntityAssociations();

        if (isset($subAssociations[$entityIdentifier])) {
            $subAssociation = $subAssociations[$entityIdentifier];
            $subRelation = $subAssociation->getRelation();
            if ($subRelation['my_id'] === $relation['foreign_id'] && $subRelation['foreign_id'] === $relation['my_id'] ) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return Select::JOIN_INNER or Select::JOIN_LEFT
     */
    public function getJoinType()
    {
        if ($this->getRequired()) {
            return \Zend\Db\Sql\Select::JOIN_INNER;
        }
        return \Zend\Db\Sql\Select::JOIN_LEFT;
    }

    public function setRequired($required)
    {
        $this->required = $required;
    }

    public function getRequired()
    {
        $relation =  $this->getParentMapper()->getRelation($this->mapperServiceName, $this->entityIdentifier);
        if (!is_null($this->required)) {
            return $this->required;
        }
        return $relation['required'];
    }

    /**
     *
     * @return array
     * @throws \UnexpectedValueException
     */
    public function getJoinColumns($aliasPrefix = null)
    {
        $hydrator = $this->getMapper()->getHydrator();
        if (!($hydrator instanceof ObservableClassMethods)) {
            throw new \UnexpectedValueException('EntityAssociation expects the mapper to have an ObservableClassMethods hydrator.');
        }
        $excludedColumns = $this->getMapper()->getEntityAssociationColumns();
        $columns = $hydrator->extract($this->getMapper()->getEntityPrototype());
        foreach ($columns as $key => & $column) {
            if (
                array_search($key, $excludedColumns) !== false
            ) {
                unset($columns[$key]);
                continue;
            }
            $column = $this->getAlias($aliasPrefix) . '__' . $key;
        }

        return array_flip($columns);
    }

    public function getPrototype()
    {
        return $this->getMapper()->getEntityPrototype();
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

    public function applyBaseEntityId($baseEntity, $associatedEntity)
    {
        $formatter = new \Zend\Filter\Word\UnderscoreToCamelCase;
        if (!is_object($baseEntity)) {
            throw new \InvalidArgumentException('Look me line!');
        }
        if (!is_object($associatedEntity)) {
            throw new \InvalidArgumentException('Look me line!');
        }

        $relation           = $this->getAssociationRequiredRelation();
        $keys               = array_keys($relation);
        $baseColumn         = current($keys);
        $associatedColumn   = current($relation);

        $mapping            = $this->getMapper()->getEntityColumnMapping();

        $baseColumn         = array_search($baseColumn, $mapping)?: $baseColumn;
        $associatedColumn   = array_search($associatedColumn, $mapping)?: $associatedColumn;

        $getBaseEntityId = 'get' . $formatter($baseColumn);
        $setAssociatedEntityId = 'set' . $formatter($associatedColumn);

        if (!method_exists($associatedEntity, $setAssociatedEntityId)) {
            throw new \UnexpectedValueException('Very very unexpected 1!');
        }
        if (!method_exists($baseEntity, $getBaseEntityId)) {
            throw new \UnexpectedValueException('Very very unexpected 2!');
        }
        $associatedEntity->$setAssociatedEntityId($baseEntity->$getBaseEntityId());
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

    /**
     * @return AbstractMapper
     * @throws \InvalidArgumentException
     */
    public function getParentMapper()
    {
        $mapper = $this->getServiceManager()->get($this->parentMapperServiceName);
        if (!is_object($mapper) || !($mapper instanceof AbstractMapper)) {
            throw new \InvalidArgumentException('Mapper must be an instance of AbstractMapper');
        }
        return $mapper;
    }

    /**
     * @return AbstractMapper
     * @throws \InvalidArgumentException
     */
    public function getMapper()
    {
        $mapper = $this->getServiceManager()->get($this->mapperServiceName);
        if (!is_object($mapper) || !($mapper instanceof AbstractMapper)) {
            throw new \InvalidArgumentException('Mapper must be an instance of AbstractMapper');
        }
        /*$hydrator = $this->getHydrator();
        if (!empty($hydrator)) {
            $mapper->setHydrator($this->getHydrator());
        }*/

        return $mapper;
    }

    /*public function setHydrator(ObservableClassMethods $hydrator)
    {
        $this->hydrator = $hydrator;
        return $this;
    }

    public function getHydrator()
    {
        return $this->hydrator;
    }*/
}

