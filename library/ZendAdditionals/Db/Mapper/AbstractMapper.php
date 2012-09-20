<?php

namespace ZendAdditionals\Db\Mapper;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Adapter\Driver\ResultInterface;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\TableIdentifier;
use Zend\Stdlib\Hydrator\HydratorInterface;
use Zend\Stdlib\Hydrator\ClassMethods;
use ZendAdditionals\Stdlib\Hydrator\ObservableClassMethods;
use ZendAdditionals\Stdlib\Hydrator\Strategy\ObservableStrategyInterface;
use ZendAdditionals\Db\Adapter\MasterSlaveAdapterInterface;
use ZendAdditionals\Db\EntityAssociation\EntityAssociation;
use ZendAdditionals\Db\EntityAssociation\EntityAssociationAwareInterface;
use ZendAdditionals\Db\ResultSet\JoinedHydratingResultSet;
use Zend\Db\Adapter\AdapterAwareInterface;
use Zend\ServiceManager\ServiceManagerAwareInterface;
use Zend\ServiceManager\ServiceManager;

class AbstractMapper extends \Application\EventProvider implements
    ServiceManagerAwareInterface,
    EntityAssociationAwareInterface,
    AdapterAwareInterface
{
    /**
     * @var Adapter
     */
    protected $dbAdapter;

    /**
     * @var Adapter
     */
    protected $dbSlaveAdapter;

    /**
     * @var ObservableClassMethods
     */
    protected $hydrator;

    /**
     * @var object
     */
    protected $entityPrototype;

    /**
     * @var Select
     */
    protected $selectPrototype;

    /**
     * @var Sql
     */
    private $sql;

    /**
     * @var Sql
     */
    private $slaveSql;

    /**
     * @var string
     */
    protected $tableName;

    /**
     * @var boolean
     */
    private $isInitialized = false;

    /**
     * array containing arrays of keys that are combined to primary keys
     * @var type
     */
    protected $primaries = array();

    /**
     * Identifier of a column that gets an auto generated value, only one is possible'
     *
     * @var string
     */
    protected $autoGenerated;

    protected $entityAssociations = array();

    protected $serviceManager;

    public function initializeEntityAssociations() { }

    /**
     * @param EntityAssociation $association
     */
    protected function addEntityAssociation(EntityAssociation $association)
    {
        $association->setServiceManager($this->getServiceManager());
        $this->entityAssociations[$association->getAlias()] = $association;
    }

    /**
     * Performs some basic initialization setup and checks before running a query
     * @return null
     */
    protected function initialize()
    {
        if ($this->isInitialized) {
            return;
        }

        if (!$this->dbAdapter instanceof Adapter) {
            throw new \Exception('No db adapter present');
        }

        if (!$this->hydrator instanceof HydratorInterface) {
            $this->hydrator = new ClassMethods;
        }

        if (!is_object($this->entityPrototype)) {
            throw new \Exception('No entity prototype set');
        }

        $this->isInitialized = true;
    }

    /**
     * @param ServiceManager $serviceManager
     * @return AbstractMapper
     */
    public function setServiceManager(ServiceManager $serviceManager)
    {
        $this->serviceManager = $serviceManager;
        return $this;
    }

    public function getServiceManager()
    {
        return $this->serviceManager;
    }

    protected function getJoinObjectProtoTypes()
    {
        $prototypes = array();
        foreach ($this->entityAssociations as $identifier => $association) {
            $prototypes[$identifier] = $association->getPrototype();
        }
        return $prototypes;
    }

    /**
     * @param string|null $table
     * return Select
     */
    protected function getSelect($table = null)
    {
        $this->initialize();
        return $this->getSlaveSql()->select($table);
    }

    /**
     * @param Select $select
     * @return JoinedHydratingResultSet
     */
    protected function select(Select $select)
    {
        $this->initialize();

        $stmt = $this->getSlaveSql()->prepareStatementForSqlObject($select);

        $resultSet = new JoinedHydratingResultSet($this->getHydrator(), $this->getEntityPrototype());
        $resultSet->setObjectPrototypes($this->getJoinObjectProtoTypes());

        $resultSet->initialize($stmt->execute());

        return $resultSet;
    }

    /**
     *
     * @param object $entity
     * @param boolean $saveAssociated Save associated entities as well?
     * @return ResultInterface
     * @throws \Exception
     */
    public function save($entity, $saveAssociated = true)
    {
        $this->initialize();

        $hydrator = $this->getHydrator();
        $skipped  = array();

        foreach ($this->entityAssociations as $association) {
            $associatedEntity = $association->getAssociatedEntity($entity);
            $currentAssociatedEntityId = $association->getCurrentAssociatedEntityId($entity);
            $associationPrototype = $association->getPrototype();

            if (!is_object($associatedEntity)) {
                if ($association->getRequired() && empty($currentAssociatedEntityId)) {
                    throw new \Exception('Associated entity "' . get_class($associationPrototype) . '" for entity "' . get_class($entity) . '" is required!');
                }
                continue;
            }
            if ($saveAssociated) {
                if (
                    $association->getRequiredByAssociation() &&
                    !$hydrator->hasOriginal($entity) &&             // When this entity is not stored into the database yet
                    !$association->hasBaseEntity($associatedEntity) // And the associated is not linked to another base entity
                ) {
                    // We skip this associated entity until after the current base entity has been
                    // stored into the database.
                    $skipped[] = array(
                        'entity' => $associatedEntity,
                        'association' => $association,
                    );
                    continue;
                }
                $association->saveAssociatedEntity($associatedEntity);
            }
            $association->applyAssociatedEntityId($entity, $associatedEntity);
        }

        $result = false;
        if ($hydrator->hasOriginal($entity)) {
            $result = $this->update($entity, null, $hydrator);
        } else {
            $result = $this->insert($entity, $hydrator);
        }

        if (!empty($skipped)) {
            foreach ($skipped as $skipInfo) {
                $skippedEntity = $skipInfo['entity'];
                $association = $skipInfo['association'];
                $association->saveAssociatedEntity($skippedEntity);
                $association->applyAssociatedEntityId($entity, $skippedEntity);
            }
            $this->save($entity, false);
        }
        return $result;
    }

    /**
     * @param object|array $entity
     * @param HydratorInterface|null $hydrator
     * @return ResultInterface
     */
    protected function insert($entity, HydratorInterface $hydrator = null)
    {
        $this->initialize();
        $tableName = $this->tableName;

        $sql = $this->getSql()->setTable($tableName);
        $insert = $sql->insert();

        $autoGeneratedGet = 'get' . ucfirst($this->autoGenerated);
        if (null !== $entity->$autoGeneratedGet()) {
            throw new \Exception('Can not insert data that already has an auto generated value!');
        }

        $rowData = $this->entityToArray($entity, $hydrator);

        // Put in better function
        foreach ($rowData as $key => $data) {
            if (isset($this->entityAssociations[$key])) {
                unset($rowData[$key]);
            }
        }

        // If applicable remove auto generated column for insert
        if (!empty($this->autoGenerated)) {
            unset($rowData[$this->autoGenerated]);
        }

        $insert->values($rowData);

        $statement = $sql->prepareStatementForSqlObject($insert);
        /*@var $statement \Zend\Db\Adapter\Driver\Pdo\Statement*/

        $result = $statement->execute();
        /*@var $result \Zend\Db\Adapter\Driver\Pdo\Result*/

        if (
            null !== $this->autoGenerated &&
            null !== ($generatedValue = $result->getGeneratedValue())
        ) {
            $autoGeneratedSet = 'set' . ucfirst($this->autoGenerated);
            $entity->$autoGeneratedSet($generatedValue);
        }

        $hydrator->setChangesCommitted($entity);

        return $result;
    }

    /**
     * @param object|array $entity
     * @param string|array|closure $where
     * @param HydratorInterface|null $hydrator
     * @return ResultInterface
     */
    protected function update($entity, $where = null, HydratorInterface $hydrator = null)
    {
        $this->initialize();
        $tableName = $this->tableName;

        $sql = $this->getSql()->setTable($tableName);
        $update = $sql->update();

        $originalData = array();
        $changedData = $this->entityToArray($entity, $hydrator, true, $originalData);

        // Put in better function
        foreach ($changedData as $key => $data) {
            if (isset($this->entityAssociations[$key])) {
                unset($changedData[$key]);
            }
        }

        if (empty($changedData)) {
            return true;
        }

        if (empty($where)) {
            if ($this->isPrimaryKeyChanged($changedData)) {
                $previousPrimaryData = $this->getPrimaryData($originalData);
                if (empty($previousPrimaryData)) {
                    die('Can not update a non existing entity!');
                }
                $where = $previousPrimaryData;
            } else {
                $where = $this->getPrimaryData($this->entityToArray($entity, $hydrator));
            }
        }

        $update->set($changedData)
            ->where($where);

        $statement = $sql->prepareStatementForSqlObject($update);
        /*@var $statement \Zend\Db\Adapter\Driver\Pdo\Statement*/

        $result = $statement->execute();

        $hydrator->setChangesCommitted($entity);

        return $result;
    }

    /**
     * Check for a specific entity if the primary key has been changed
     *
     * @return boolean
     */
    protected function isPrimaryKeyChanged(array $changedData)
    {
        foreach ($this->primaries as $primary) {
            foreach ($primary as $key) {
                if (isset($changedData[$key])) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Extract the primary data from an array of data extracted from the entity, this should be
     * the original data provided by reference when calling entityToArray
     *
     * @param array $data
     *
     * @return array
     */
    protected function getPrimaryData(array $data)
    {
        $return = array();
        foreach ($this->primaries as $primary) {
            foreach ($primary as $key) {
                if (isset($data[$key])) {
                    $return[$key] = $data[$key];
                }
            }
        }
        return $return;
    }

    /**
     * @param string|array|closure $where
     * @param string|TableIdentifier|null $tableName
     * @return ResultInterface
     */
    protected function delete($where, $tableName = null)
    {
        $tableName = $tableName ?: $this->tableName;

        $sql = $this->getSql()->setTable($tableName);
        $delete = $sql->delete();

        $delete->where($where);

        $statement = $sql->prepareStatementForSqlObject($delete);

        return $statement->execute();
    }

    /**
     * @return string
     */
    protected function getTableName()
    {
        return $this->tableName;
    }

    /**
     * @return object
     */
    public function getEntityPrototype()
    {
        return $this->entityPrototype;
    }

    /**
     * @param object $modelPrototype
     * @return AbstractDbMapper
     */
    public function setEntityPrototype($entityPrototype)
    {
        $this->entityPrototype = $entityPrototype;
        return $this;
    }

    /**
     * @return Adapter
     */
    public function getDbAdapter()
    {
        return $this->dbAdapter;
    }

    /**
     * @param Adapter $dbAdapter
     * @return AbstractDbMapper
     */
    public function setDbAdapter(Adapter $dbAdapter)
    {
        $this->dbAdapter = $dbAdapter;
        if ($dbAdapter instanceof MasterSlaveAdapterInterface) {
            $this->setDbSlaveAdapter($dbAdapter->getSlaveAdapter());
        }
        return $this;
    }

    /**
     * @return Adapter
     */
    public function getDbSlaveAdapter()
    {
        return $this->dbSlaveAdapter ?: $this->dbAdapter;
    }

    /**
     * @param Adapter $dbAdapter
     * @return AbstractDbMapper
     */
    public function setDbSlaveAdapter(Adapter $dbSlaveAdapter)
    {
        $this->dbSlaveAdapter = $dbSlaveAdapter;
        return $this;
    }

    /**
     * @return HydratorInterface
     */
    public function getHydrator()
    {
        return $this->hydrator;
    }

    /**
     * @param HydratorInterface $hydrator
     * @return AbstractDbMapper
     */
    public function setHydrator(HydratorInterface $hydrator)
    {
        if (!($hydrator instanceof ObservableStrategyInterface)) {
            throw new \InvalidArgumentException('Hydrator must implement ObservableStrategyInterface');
        }
        $this->hydrator = $hydrator;
        return $this;
    }

    /**
     * @return Sql
     */
    protected function getSql()
    {
        if (!$this->sql instanceof Sql) {
            $this->sql = new Sql($this->getDbAdapter());
        }

        return $this->sql;
    }

    /**
     * @param Sql
     * @return AbstractDbMapper
     */
    protected function setSql(Sql $sql)
    {
        $this->sql = $sql;
        return $this;
    }

    /**
     * @return Sql
     */
    protected function getSlaveSql()
    {
        if (!$this->slaveSql instanceof Sql) {
            $this->slaveSql = new Sql($this->getDbSlaveAdapter());
        }

        return $this->slaveSql;
    }

    /**
     * @param Sql
     * @return AbstractDbMapper
     */
    protected function setSlaveSql(Sql $sql)
    {
        $this->slaveSql = $sql;
        return $this;
    }

    /**
     * Uses the hydrator to convert the entity to an array.
     *
     * Use this method to ensure that you're working with an array.
     *
     * @param object $entity
     * @return array
     */
    protected function entityToArray(
        $entity,
        HydratorInterface $hydrator = null,
        $changesOnly = false,
        & $originalData = null
    ) {
        if (is_array($entity)) {
            return $entity; // cut down on duplicate code
        } elseif (is_object($entity)) {
            if (!$hydrator) {
                $hydrator = $this->getHydrator();
            }
            $originalData = $hydrator->extractOriginal($entity);
            if ($changesOnly) {
                $entityArray = $hydrator->extractChanges($entity);
            } else {
                $entityArray = $hydrator->extract($entity);
            }
            return $entityArray;
        }
        throw new Exception\InvalidArgumentException('Entity passed to db mapper should be an array or object.');
    }
}

