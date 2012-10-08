<?php
namespace ZendAdditionals\Db\Mapper;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Adapter\Driver\ResultInterface;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Sql;
use Zend\Stdlib\Hydrator\HydratorInterface;
use Zend\Stdlib\Hydrator\ClassMethods;
use ZendAdditionals\Stdlib\Hydrator\ObservableClassMethods;
use ZendAdditionals\Stdlib\Hydrator\Strategy\ObservableStrategyInterface;
use ZendAdditionals\Db\Adapter\MasterSlaveAdapterInterface;
use ZendAdditionals\Db\EntityAssociation\EntityAssociation;
use ZendAdditionals\Db\ResultSet\JoinedHydratingResultSet;
use Zend\Db\Adapter\AdapterAwareInterface;
use Zend\ServiceManager\ServiceManagerAwareInterface;
use Zend\ServiceManager\ServiceManager;

use Zend\Db\Sql\Predicate\Predicate;
use Zend\Db\Sql\Predicate\Operator;

use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\EventManager;

class AbstractMapper implements
    ServiceManagerAwareInterface,
    AdapterAwareInterface
{
    const SERVICE_NAME           = '';

    const RELATION_TYPE_FOREIGN  = 'foreign';
    const RELATION_TYPE_MY       = 'my';
    const RELATION_TYPE_VALUE    = 'value';
    const RELATION_TYPE_CALLBACK = 'callback';

    const OPERAND_EQUALS         = Operator::OPERATOR_EQUAL_TO;
    const OPERAND_NOT_EQUALS     = Operator::OPERATOR_NOT_EQUAL_TO;
    const OPERAND_IN             = 'in';
    const OPERAND_LESS           = Operator::OPERATOR_LESS_THAN;
    const OPERAND_LESS_OR_EQUALS = Operator::OPERATOR_LESS_THAN_OR_EQUAL_TO;
    const OPERAND_MORE           = Operator::OPERATOR_GREATER_THAN;
    const OPERAND_MORE_OR_EQUALS = Operator::OPERATOR_GREATER_THAN_OR_EQUAL_TO;

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
     * Identifier of a column that gets an auto generated value,
     * only one is possible'
     *
     * @var string
     */
    protected $autoGenerated;

    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * @var array
     */
    protected $relations = array();

    /**
     * @var \SplObjectStorage
     */
    protected $entityAssociationStorage;

    /** @var array */
    protected $extraJoinRequiredKeys        = array('operand', 'left', 'right');

    /** @var array */
    protected $extraJoinColumnRequiredKeys  = array('value', 'type');

    /** @var EventManagerInterface */
    protected static $eventManager;

    /**
     * @var boolean
     */
    protected $tablePrefixRequired = false;

    protected $relationsByServiceName = array();

    protected $attributeRelations = array();

    protected $attributeRelationsGenerated = false;

    public function __construct()
    {
        if (static::$eventManager === null) {
            $this->setEventManager(new EventManager());
        }
    }

    /**
     * Set the event manager
     *
     * @param EventManagerInterface $eventManager
     *
     * @return AbstractMapper
     */
    public function setEventManager(EventManagerInterface $eventManager)
    {
        static::$eventManager = $eventManager;
        return $this;
    }

    /**
     * @return EventManagerInterface
     */
    public function getEventManager()
    {
        return static::$eventManager;
    }

    protected function initializeRelations()
    {
        if (!$this->attributeRelationsGenerated) {
            if (isset($this->attributeRelations['attributes']) && is_array($this->attributeRelations['attributes'])) {
                foreach ($this->attributeRelations['attributes'] as $attributeLabel) {
                    $this->generateAttributeRelation($attributeLabel);
                }
            }
            $this->attributeRelationsGenerated = true;
        }

        if (empty($this->relations)) {
            return;
        }

        if (empty($this->relationsByServiceName)) {
            foreach ($this->relations as $identifier => &$relation) {
                $this->relationsByServiceName[$relation['mapper_service_name']][$identifier] = $relation;
            }
        }
    }

    /**
     * @param string $mapperServiceName
     * @param string $entityIdentifier
     *
     * @return array
     */
    public function getRelation($mapperServiceName, $entityIdentifier)
    {
        if (!isset($this->relationsByServiceName[$mapperServiceName][$entityIdentifier])) {
            throw new \UnexpectedValueException(
                'Dit not expect the requested relation not to exist.'
            );
        }
        return $this->relationsByServiceName[$mapperServiceName][$entityIdentifier];
    }

    protected function generateAttributeRelation($label)
    {
        if (
            !isset($this->attributeRelations['attributes']) ||
            ($key = array_search($label, $this->attributeRelations['attributes'])) === false
        ) {
            throw new \UnexpectedValueException(
                'There is no attribute relation defined for label "' . $label . '"!'
            );
        }

        $property = is_numeric($key) ? $label : $key;

        if (isset($this->relations[$property])) {
            return;
        }

        if (!isset($this->attributeRelations['table_prefix'])) {
            throw new \UnexpectedValueException(
                'There is no attribute table prefix defined for label "' . $label . '"!'
            );
        }
        if (!isset($this->attributeRelations['relation_column'])) {
            throw new \UnexpectedValueException(
                'There is no attribute relation column defined for label "' . $label . '"!'
            );
        }

        $this->relations[$property] = array(
            'mapper_service_name'  => AttributeData::SERVICE_NAME,
            'required'             => false,
            'foreign_table_prefix' => $this->attributeRelations['table_prefix'],
            'back_reference'       => array(
                'entity_id' => $this->attributeRelations['relation_column'],
            ),
            'extra_conditions'     => array(
                array(
                    'left' => array(
                        'type'    => self::RELATION_TYPE_FOREIGN,
                        'value'   => 'attribute_id',
                    ),
                    'operand' => self::OPERAND_EQUALS,
                    'right'   => array(
                        'type'     => self::RELATION_TYPE_CALLBACK,
                        'value'    => array($label, $this->attributeRelations['table_prefix']),
                        'callback' => array($this, 'getAttributeIdByLabel'),
                    ),
                ),
            ),
        );
    }

    /**
     * Performs some basic initialization setup and checks before
     * running a query
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

        $this->initializeRelations();

        $this->isInitialized = true;
    }

    /**
     * @param ServiceManager $serviceManager
     * @return AbstractMapper
     */
    public function setServiceManager(ServiceManager $serviceManager)
    {
        $this->serviceManager = $serviceManager;

        if (!$this->serviceManager->has(Attribute::SERVICE_NAME)) {
            $this->serviceManager->setFactory(
                Attribute::SERVICE_NAME,
                'ZendAdditionals\Service\AttributeMapperServiceFactory'
            );
            $this->serviceManager->setFactory(
                AttributeData::SERVICE_NAME,
                'ZendAdditionals\Service\AttributeDataMapperServiceFactory'
            );
            $this->serviceManager->setFactory(
                AttributeProperty::SERVICE_NAME,
                'ZendAdditionals\Service\AttributePropertyMapperServiceFactory'
            );
        }

        return $this;
    }

    /**
     * @return ServiceManager
     */
    public function getServiceManager()
    {
        return $this->serviceManager;
    }

    /**
     * Returns an array of column identifiers used for entities that are related
     *
     * @return array
     */
    public function getEntityAssociationColumns()
    {
        $this->initialize();
        return array_keys($this->relations);
    }

    /**
     * Check if this mapper requires a table prefix
     *
     * @return boolean
     */
    public function getTablePrefixRequired()
    {
        return $this->tablePrefixRequired;
    }

    /**
     * @param string|null $table
     * return Select
     */
    protected function getSelect($table = null)
    {
        $table = $table ?: $this->tableName;
        $this->initialize();
        return $this->getSlaveSql()->select($table);
    }

    /**
     * @param Select $select
     * @return JoinedHydratingResultSet
     */
    protected function getResult(Select $select)
    {
        $this->initialize();
        $stmt = $this->getSlaveSql()->prepareStatementForSqlObject($select);
        $resultSet = new JoinedHydratingResultSet(
            $this->getHydrator(),
            $this->getEntityPrototype()
        );

        $resultSet->setEventManager($this->getEventManager());

        $associations = $this->getEntityAssociationsForSelect($select);
        if(!empty($associations)) {
            $associations = array_reverse($associations, true);
            $resultSet->setAssociations($associations);
        }

        $resultSet->initialize($stmt->execute());

        $this->resetEntityAssociationStorage($select);

        return $resultSet;
    }

    protected function getCurrent(Select $select)
    {
        return $this->getResult($select)->current();
    }

   protected function debugSql($sql) {
        $sql = preg_replace('/[\r\n]/', '', $sql);
        $sql = preg_replace('/\s+/', ' ', $sql);

        if (
            preg_match_all(
                '/SELECT(.+?)FROM/si',
                $sql,
                $matches,
                PREG_SET_ORDER
            )
        ) {
            foreach($matches as $match) {
                $sql = str_replace(
                    $match[0],
                    'SELECT' . str_replace(',', ",\n    ", $match[1]) . 'FROM',
                    $sql
                );
            }
        }

        $needles = array(
            '/\s+(FROM|LEFT JOIN|INNER JOIN|RIGHT JOIN|JOIN|' .
                'UNION ALL|WHERE|LIMIT|VALUES|GROUP BY)\s+/',
            '/\s(SELECT)\s+/',
            '/\s+(INSERT INTO|UPDATE INTO|UPDATE|`,)\s+/',
            '/\s+(AND|OR |ORDER)\s+/',
            '/;/',
        );

        $replaces = array(
            "\n$1 \n    ",
            "$1 \n  ",
            "$1 \n  ",
            "\n  $1 ",
            ";\n",
        );

        $nice = preg_replace($needles, $replaces, $sql) . "\n";

        return $nice;
    }

    /**
     * Add a join
     *
     * @param Select $select
     * @param string $entityIdentifier
     * @param EntityAssociation $parentAssociation
     * @param string The prefix for joined table
     *
     * @return EntityAssociation
     *
     * @throws \UnexpectedValueException
     */
    protected function addJoin(
        Select $select,
        $entityIdentifier,
        EntityAssociation $parentAssociation = null
    ) {
        $this->initialize();
        // First get the correct mapper
        $mapper = $this;
        if ($parentAssociation instanceof EntityAssociation) {
            $mapper = $parentAssociation->getMapper();
        }

        // Check if the relation information has been defined
        if (!isset($mapper->relations[$entityIdentifier])) {
            throw new \UnexpectedValueException(
                'The given associated entity identifier "' .
                $entityIdentifier . '" is not defined in the relations!'
            );
        }

        $relation = $mapper->relations[$entityIdentifier];

        // Create the entity association based on the relation information
        $entityAssociation = new EntityAssociation(
            $entityIdentifier,
            $mapper::SERVICE_NAME,
            $relation['mapper_service_name']
        );

        if ($parentAssociation instanceof EntityAssociation) {
            $entityAssociation->setParentAlias($parentAssociation->getAlias());
        }

        $joinTableAlias = $this->getTableName();

        if ($parentAssociation instanceof EntityAssociation) {
            $joinTableAlias = $parentAssociation->getAlias();
            if (!$parentAssociation->getRequired()) {
                $entityAssociation->setRequired(false);
            }
        }

        $entityAssociation->setServiceManager($this->getServiceManager());

        $tablePrefix = isset($relation['foreign_table_prefix']) ?
            $relation['foreign_table_prefix'] :
            null;

        if (
            $parentAssociation instanceof EntityAssociation &&
            isset($relation['recursive_table_prefix'])
        ) {
            $tablePrefix = $parentAssociation->getTablePrefix();
        }

        $entityAssociation->setTablePrefix($tablePrefix);

        if (
            !isset($relation['reference']) &&
            !isset($relation['back_reference'])
        ) {
            throw new \UnexpectedValueException(
                'When using joins either reference or back reference must be present!'
            );
        }
        $referenceInformation = isset($relation['reference']) ?
            $relation['reference'] :
            array_flip($relation['back_reference']);

        $predicate = $entityAssociation->getPredicate();


        foreach ($referenceInformation as $myId => $foreignId) {
            $predicate->equalTo(
                $entityAssociation->getAlias() . '.' . $foreignId,
                $joinTableAlias . '.' . $myId,
                Predicate::TYPE_IDENTIFIER,
                Predicate::TYPE_IDENTIFIER
            );
        }

        if(isset($relation['extra_conditions'])) {
            if (!is_array($relation['extra_conditions'])) {
                throw new \UnexpectedValueException(
                    'extra_conditions should be an array for ' .
                    $entityIdentifier
                );
            }

            foreach($relation['extra_conditions'] as $relation) {
                $this->addExtraJoin(
                    $relation,
                    $predicate,
                    $entityAssociation,
                    $joinTableAlias
                );
            }
        }

        $select->join(
            $entityAssociation->getJoinTable(),
            $entityAssociation->getPredicate(),
            $entityAssociation->getJoinColumns(),
            $entityAssociation->getJoinType()
        );

        $this->storeEntityAssociationToSelect($select, $entityAssociation);

        return $entityAssociation;
    }

    /**
     * Create joins for all possible attributes
     *
     * @param Select $select
     */
    protected function addAttributeJoins(Select $select)
    {
        if (
            isset($this->attributeRelations['attributes']) &&
            is_array($this->attributeRelations['attributes'])
        ) {
            foreach ($this->attributeRelations['attributes'] as $attribute) {
                $this->addAttributeJoin($select, $attribute);
            }
        }
    }

    /**
     * Create join for a specific attribute
     *
     * @param Select $select
     * @param type $attribute
     */
    protected function addAttributeJoin(Select $select, $attribute)
    {
        $ref = $this->addJoin($select, $attribute);
        $this->addJoin($select, 'attribute', $ref);
        $this->addJoin($select, 'attribute_property', $ref);
    }

    /**
     * Generates extra joins based on the information provider in the
     * relations extra_conditions
     *
     * @param array $extraJoin      This is the array defined in the
     *                              relations section of the mapper
     * @param Predicate $predicate  The predicate that will be used for
     *                              the joins
     * @param EntityAssociation $entityAssociation The entityAssociation that
     *                                             controls the join
     *
     * @return void
     * @throws \UnexpectedValueException
     */
    protected function addExtraJoin(
        $extraJoin,
        Predicate $predicate,
        EntityAssociation $entityAssociation,
        $myJoinAlias
    ) {
        $diff = array_diff_key(
            array_flip($this->extraJoinRequiredKeys),
            $extraJoin
        );
        if (count($diff) > 0) {
            throw new \UnexpectedValueException(
                'Following keys should be set for extra join: ' .
                implode(', ', array_keys($diff))
            );
        }

        list($leftValue, $leftType) = $this->normalizeValueTypeForPredicate(
            $extraJoin['left'],
            $entityAssociation->getAlias(),
            $myJoinAlias
        );

        list($rightValue, $rightType) = $this->normalizeValueTypeForPredicate(
            $extraJoin['right'],
            $entityAssociation->getAlias(),
            $myJoinAlias
        );

        // Call the correct predicate function based operand provided
        switch($extraJoin['operand']) {
            case self::OPERAND_EQUALS:
            case self::OPERAND_NOT_EQUALS:
            case self::OPERAND_LESS:
            case self::OPERAND_LESS_OR_EQUALS:
            case self::OPERAND_MORE:
            case self::OPERAND_MORE_OR_EQUALS:
                $predicate->addPredicate(
                    new Operator(
                        $leftValue,
                        $extraJoin['operand'],
                        $rightValue,
                        $leftType,
                        $rightType
                    )
                );
                break;
            default:
                throw new \UnexpectedValueException(
                    'operand `' . $extraJoin['operand'] . '` not implemented'
                );
                break;
        }
    }

    public function getAttributeIdByLabel($label, $tablePrefix)
    {
        /*@var $attributeMapper Attribute*/
        $attributeMapper = $this->getServiceManager()->get(Attribute::SERVICE_NAME);
        return $attributeMapper->getIdByLabel($label, $tablePrefix);
    }


    /**
     * This will normalize the value and type parameters to the predicate format
     *
     * @param array $extraJoin      This is the array defined in the relations
     *                              section of the mapper
     * @param mixed $foreignAlias   The alias that will be used if the
     *                              type is foreign
     * @param mixed $myAlias
     */
    protected function normalizeValueTypeForPredicate(
        $extraJoin,
        $foreignAlias,
        $myAlias
    ) {
        $diff = array_diff_key(
            array_flip($this->extraJoinColumnRequiredKeys),
            $extraJoin
        );
        if (count($diff) > 0) {
            throw new \UnexpectedValueException(
                'Following keys should be set for extraJoin: ' .
                implode(', ', array_keys($diff))
            );
        }

        $type     = $extraJoin['type'];
        $value    = $extraJoin['value'];
        $callback = isset($extraJoin['callback']) ?
            $extraJoin['callback'] :
            null;

        switch ($type) {
            case self::RELATION_TYPE_FOREIGN:
                $type = Predicate::TYPE_IDENTIFIER;
                $value = $foreignAlias.'.'.$value;
                break;
            case self::RELATION_TYPE_MY:
                $type = Predicate::TYPE_IDENTIFIER;
                $value = $myAlias.'.'.$value;
                break;
            case self::RELATION_TYPE_VALUE:
                $type = Predicate::TYPE_VALUE;
                break;
            case self::RELATION_TYPE_CALLBACK:
                $type = Predicate::TYPE_VALUE;

                if (!is_callable($callback)) {
                    throw new \UnexpectedValueException(
                        $callback . ' is not callable'
                    );
                }

                $value = call_user_func_array($callback, $value);
                break;
            default:
                throw new \UnexpectedValueException(
                    $type . ' extra_condition type is not implemented'
                );
                break;
        }

        return array($value, $type);
    }

    private function storeEntityAssociationToSelect(
        Select $select,
        EntityAssociation $entityAssociation
    ) {
        if (!($this->entityAssociationStorage instanceof \SplObjectStorage)) {
            $this->entityAssociationStorage = new \SplObjectStorage();
        }
        if (!$this->entityAssociationStorage->contains($select)) {
            $this->entityAssociationStorage->attach($select, array());
        }
        $data = $this->entityAssociationStorage[$select];
        $data[$entityAssociation->getAlias()]    = $entityAssociation;
        $this->entityAssociationStorage[$select] = $data;
    }

    private function getEntityAssociationsForSelect(Select $select)
    {
        if (
            !($this->entityAssociationStorage instanceof \SplObjectStorage) ||
            !$this->entityAssociationStorage->contains($select)
        ) {
            return;
        }
        return $this->entityAssociationStorage[$select];
    }

    private function resetEntityAssociationStorage(Select $select)
    {
        if (
            !($this->entityAssociationStorage instanceof \SplObjectStorage) ||
            !$this->entityAssociationStorage->contains($select)
        ) {
            return;
        }
        $this->entityAssociationStorage->detach($select);
    }

    protected function underscoreToCamelCase($underscored)
    {
        $underscored = strtolower($underscored);
        return preg_replace('/_(.?)/e',"strtoupper('$1')",$underscored);
    }

    /**
     * Save the given entity
     *
     * @param object $entity
     * @param string $tablePrefix
     * @param array  $parentRelationInfo When this save is called from a parent antity
     * the relational info from the parent is passed thru.
     *
     * @return ResultInterface|bool Boolean true gets returned
     * when there is nothing to update
     *
     * @throws \Exception
     */
    public function save($entity, $tablePrefix = null, array $parentRelationInfo = null)
    {
        if ($this->getTablePrefixRequired() && empty($tablePrefix)) {
            throw new \UnexpectedValueException(
                'This mapper requires a table prefix to ' .
                'be given when calling save.'
            );
        }

        $this->initialize();

        if (get_class($entity) !== get_class($this->getEntityPrototype())) {
            throw new \UnexpectedValueException(
                'Dit not expect the given entity of type: ' .
                get_class($entity) . '. The type: ' .
                get_class($this->getEntityPrototype()) . ' should be given.'
            );
        }

        $this->getEventManager()->trigger(
            'preSave',
            $this,
            array(
                'entity'                => $entity,
                'table_prefix'          => $tablePrefix,
                'parent_relation_info'  => $parentRelationInfo,
            )
        );

        $hydrator = $this->getHydrator();

        $result = false;
        if (
            $hydrator->hasOriginal($entity) &&
            !$this->isEntityEmpty($entity, true)
        ) {
            $result = $this->update($entity, null, $hydrator, $tablePrefix);
        } else {
            $result = $this->insert($entity, $hydrator, $tablePrefix);
        }

        return $result;
    }

    /**
     * @param object|array $entity
     * @param ObservableStrategyInterface|null $hydrator
     *
     * @return ResultInterface
     */
    protected function insert(
        $entity,
        ObservableStrategyInterface $hydrator = null,
        $tablePrefix = null
    ) {
        $this->storeRelatedEntities($entity, $tablePrefix, true);

        $this->initialize();
        $tableName = $this->getTableName();

        if (!empty($tablePrefix)) {
            $tableName = $tablePrefix . $tableName;
        }

        $sql = $this->getSql()->setTable($tableName);
        $insert = $sql->insert();

        if (!empty($this->autoGenerated)) {
            $autoGeneratedGet = 'get' . ucfirst($this->autoGenerated);
            if (null !== $entity->$autoGeneratedGet()) {
                throw new \Exception(
                    'Can not insert data that already ' .
                    'has an auto generated value!'
                );
            }
        }

        $rowData = $this->entityToArray($entity, $hydrator);

        // Put in better function
        $associatedColumns = $this->getEntityAssociationColumns();
        foreach ($associatedColumns as $associatedColumn) {
            if (array_key_exists($associatedColumn, $rowData)) {
                unset($rowData[$associatedColumn]);
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


        $this->storeRelatedEntities($entity, $tablePrefix);

        return $this->save($entity, $tablePrefix);
    }

    protected function unsetRelatedEntityColumns(& $entityArray)
    {
        $columns = $this->getEntityAssociationColumns();
        foreach ($columns as $column) {
            if (array_key_exists($column, $entityArray)) {
                unset($entityArray[$column]);
            }
        }
        return $entityArray;
    }

    protected function storeRelatedEntities(
        $entity,
        $tablePrefix = null,
        $ignoreEntitiesThatRequireBase = false
    ) {
        $this->initialize();
        foreach ($this->relations as $entityIdentifier => $relationInfo) {
            $getAssociatedEntity = $this->underscoreToCamelCase(
                'get_' . $entityIdentifier
            );
            $associatedEntity = $entity->$getAssociatedEntity();

            /*
             * The associated entity is null when not added to the join when
             * selecting and the associated entity is empty when it has been
             * added to the join but does not exist in the database
             * (left outer join)
             */
            if (
                is_null($associatedEntity) ||
                $this->isEntityEmpty($associatedEntity)
            ) {
                continue;
            }

            /* @var $relationServiceMapper AbstractMapper */
            $relationServiceMapper = $this->getServiceManager()->get(
                $relationInfo['mapper_service_name']
            );

            // Check if the base entity has a relation to the associated entity
            $entityHasReferenceToAssociation = isset($relationInfo['reference']);


            // Check if the associated entity has a relation back to me
            $associationHasBackReference = isset($relationInfo['back_reference']);

            /*
             * When the associated entity relates back check if we want
             * to ignore this entity
             */
            if ($associationHasBackReference && $ignoreEntitiesThatRequireBase) {
                continue;
            }

            // Set the relation id's when the association relates back
            if ($associationHasBackReference) {
                foreach ($relationInfo['back_reference'] as $foreignId => $myId) {
                    // Set id from entity into associated entity
                    $getMyId      = $this->underscoreToCamelCase('get_' . $myId);
                    $setForeignId = $this->underscoreToCamelCase('set_' . $foreignId);
                    $associatedEntity->$setForeignId($entity->$getMyId());
                }
            }

            /*
             * Set the table prefix to null when a recursive
             * prefix is not required
             */
            if (
                !isset($relationInfo['recursive_table_prefix']) ||
                !$relationInfo['recursive_table_prefix']
            ) {
                $tablePrefix = null;
            }

            // When the association requires a prefix we will set this
            // (this has nothing to do with recursion)
            if (isset($relationInfo['foreign_table_prefix'])) {
                $tablePrefix = $relationInfo['foreign_table_prefix'];
            }

            // Save the associated entity
            $relationServiceMapper->save(
                $associatedEntity,
                $tablePrefix,
                $relationInfo
            );

            // Set id from associated entity into entity (if applicable)
            if ($entityHasReferenceToAssociation) {
                foreach ($relationInfo['reference'] as $myId => $foreignId) {
                    $setMyId = $this->underscoreToCamelCase(
                        'set_' . $myId
                    );
                    $getForeignId = $this->underscoreToCamelCase(
                        'get_' . $foreignId
                    );
                    $entity->$setMyId($associatedEntity->$getForeignId());
                }
            }
        }
    }

    /**
     * @param object|array $entity
     * @param string|array|closure $where
     * @param ObservableStrategyInterface|null $hydrator
     * @return ResultInterface
     */
    protected function update(
        $entity,
        $where = null,
        ObservableStrategyInterface $hydrator = null,
        $tablePrefix = null
    ) {
        $this->storeRelatedEntities($entity, $tablePrefix);

        $this->initialize();
        $tableName = $this->getTableName();
        if (!empty($tablePrefix)) {
            $tableName = $tablePrefix . $tableName;
        }

        $sql = $this->getSql()->setTable($tableName);
        $update = $sql->update();

        $originalData = array();

        $changedData = $this->entityToArray($entity, $hydrator, true, $originalData);


        $this->unsetRelatedEntityColumns($changedData);

        if (empty($changedData)) {
            return true;
        }

        if (empty($where)) {
            if ($this->isPrimaryKeyChanged($changedData)) {
                $previousPrimaryData = $this->getPrimaryData($originalData);
                if (empty($previousPrimaryData)) {
                    throw new \LogicException(
                        'Update called for non existing entity, must be fixed!'
                    );
                }
                $where = $previousPrimaryData;
            } else {
                $where = $this->getPrimaryData(
                    $this->entityToArray($entity, $hydrator)
                );
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
     * Extract the primary data from an array of data extracted from the entity,
     * this should be the original data provided by reference when
     * calling entityToArray
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
     * @return string
     */
    public function getTableName()
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
     * @return ObservableStrategyInterface
     */
    public function getHydrator()
    {
        return $this->hydrator;
    }

    /**
     * @param ObservableStrategyInterface $hydrator
     * @return AbstractDbMapper
     */
    public function setHydrator(ObservableStrategyInterface $hydrator)
    {
        if (!($hydrator instanceof ObservableStrategyInterface)) {
            throw new \InvalidArgumentException(
                'Hydrator must implement ObservableStrategyInterface'
            );
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
     * @param ObservableStrategyInterface $hydrator
     * @param boolean $changesOnly
     * @param array $originalData
     *
     * @return array
     *
     * @throws Exception\InvalidArgumentException
     */
    public function entityToArray(
        $entity,
        ObservableStrategyInterface $hydrator = null,
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
        throw new \InvalidArgumentException(
            'Entity passed to db mapper should be an array or object.'
        );
    }

    /**
     * Check if the given entity is empty, it is also possible to check if the
     * original data for this entity is empty.
     *
     * @param object  $entity
     * @param boolean $checkOriginalData
     *
     * @return boolean
     */
    protected function isEntityEmpty($entity, $checkOriginalData = false)
    {
        $hydrator = $this->getHydrator();

        $rowData = $checkOriginalData ?
            $hydrator->extractOriginal($entity) :
            $hydrator->extract($entity);

        $isEmpty = true;

        foreach($rowData as $data) {
            if ($data !== null && !is_object($data)) {
                $isEmpty = false;
                break;
            }
        }

        return $isEmpty;
    }
}

