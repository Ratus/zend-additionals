<?php

namespace ZendAdditionals\Db\Mapper;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Adapter\Driver\ResultInterface;
use Zend\Db\ResultSet\HydratingResultSet;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\TableIdentifier;
use Zend\Stdlib\Hydrator\HydratorInterface;
use Zend\Stdlib\Hydrator\ClassMethods;
use ZendAdditionals\Stdlib\Hydrator\ObservableClassMethods;
use ZendAdditionals\Stdlib\Hydrator\Strategy\ObservableStrategyInterface;
use ZendAdditionals\Db\Adapter\MasterSlaveAdapterInterface;

class AbstractMapper extends \Application\EventProvider
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

    public function __construct()
    {
        $this->hydrator = new ObservableClassMethods();
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
     * @param string|null $table
     * return Select
     */
    protected function getSelect($table = null)
    {
        $this->initialize();
        return $this->getSlaveSql()->select($table);
    }

    /**
     *
     *
     * @param Select $select
     * @param object|null $entityPrototype
     * @param HydratorInterface|null $hydrator
     * @return HydratingResultSet
     */
    protected function select(Select $select, $entityPrototype = null, HydratorInterface $hydrator = null)
    {
        $this->initialize();

        $stmt = $this->getSlaveSql()->prepareStatementForSqlObject($select);

        $resultSet = new HydratingResultSet($hydrator ?: $this->getHydrator(),
            $entityPrototype ?: $this->getEntityPrototype());

        $resultSet->initialize($stmt->execute());
        return $resultSet;
    }

    public function save($entity)
    {
        $this->initialize();
        $hydrator = $this->getHydrator();
        if ($hydrator->hasOriginal($entity)) {
            return $this->update($entity, null, $hydrator);
        }
        return $this->insert($entity, $hydrator);
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

        $insert->values($rowData);

        $statement = $sql->prepareStatementForSqlObject($insert);
        /*@var $statement \Zend\Db\Adapter\Driver\Pdo\Statement*/


        try {
            $result = $statement->execute();
        } catch (\Zend\Db\Adapter\Exception\InvalidQueryException $e) {
            throw new \Exception('Query failed!');
        }
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

        try {
            $result = $statement->execute();
        } catch (\Zend\Db\Adapter\Exception\InvalidQueryException $e) {
            throw new \Exception('My Special Exception must go here');
        }

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
    protected function entityToArray($entity, HydratorInterface $hydrator = null, $changesOnly = false, & $originalData = null)
    {
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