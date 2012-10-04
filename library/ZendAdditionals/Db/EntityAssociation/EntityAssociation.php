<?php

namespace ZendAdditionals\Db\EntityAssociation;

use ZendAdditionals\Db\Mapper\AbstractMapper;
use ZendAdditionals\Stdlib\Hydrator\ObservableClassMethods;

use Zend\Db\Sql\Predicate\Predicate;

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

	/** @var Predicate */
	protected $predicate;

	protected $tablePrefix;

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
		$this->predicate               = new Predicate();
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
		return $this->getTablePrefix().$this->getMapper()->getTableName();
	}

	public function setTablePrefix($tablePrefix)
	{
		$this->tablePrefix = $tablePrefix;
		return $this;
	}

	public function getTablePrefix()
	{
		return $this->tablePrefix;
	}

	public function getJoinTable()
	{
		return array($this->getAlias() => $this->getTableName());
	}

	/**
	* @var Predicate
	*/
	public function getPredicate()
	{
		return $this->predicate;
	}

	public function getRelation()
	{
		return $this->getParentMapper()->getRelation($this->mapperServiceName, $this->entityIdentifier);
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
		return $mapper;
	}
}

