<?php
namespace ZendAdditionals\Db\Mapper;

use ZendAdditionals\Cache\LockingCacheAwareInterface;
use ZendAdditionals\Cache\LockingCacheAwareTrait;
use Zend\Db\Sql\Predicate;
use ZendAdditionals\Stdlib\ObjectUtils;
use ZendAdditionals\Db\Mapper\Exception;
use ZendAdditionals\Stdlib\StringUtils;
use ZendAdditionals\Stdlib\ArrayUtils;
use Zend\EventManager\Event;
use Zend\Stdlib\ErrorHandler;

abstract class AbstractCachedMapper extends AbstractMapper implements
LockingCacheAwareInterface
{
    use LockingCacheAwareTrait;

    /**
     * @var boolean
     */
    protected $entityCacheEnabled = true;

    /**
     * List of default relating entities to include by default
     * NOTE: Changing this changes the cache keys
     * @var array
     */
    protected $entityCacheDefaultIncludes = array();

    /**
     * @var integer
     */
    protected $entityCacheTtl = 3600;

    /**
     * Contains serialized information of objects loaded through cache!
     *
     * @var array
     */
    protected $entityCacheObjectStorage = array();

    /**
     * Contains a list of extra identifiers tracked for this entity
     * NOTE: Changing this changes the cache keys
     * @var array
     */
    protected $entityCacheTrackedIdentifiers = array();

    /**
     * In here add some default filters
     * @var array
     */
    protected $entityCacheDefaultFilters = array();

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct();
        $this->attachToEntityPreSave();
        $this->attachToEntitySaved();
    }

    /**
     * Fetch a specific entity by the primary identifier. When entity caching
     * has been enabled on the mapper all items will be stored within cache
     * and saved to cache when modified.
     *
     * @param  integer $id
     * @return object|false
     */
    public function fetchEntityBy($identifier, $id)
    {
        $result = $this->fetchEntityCollectionBy($identifier, array($id));
        if (isset($result[$id]) && $result[$id] instanceof \ArrayIterator) {
            if ($result[$id]->count() > 1) {
                throw new Exception\LogicException(
                    'FetchEntityBy expects only one result to be found!, ' .
                    'a collection was found!'
                );
            }
            return $result[$id][0];
        }
        return false;
    }

    /**
     * Fetch a specific entity by the primary identifier. When entity caching
     * has been enabled on the mapper all items will be stored within cache
     * and saved to cache when modified.
     *
     * @param  integer $id
     * @return object|false
     */
    public function fetchEntityById($id)
    {
        $key       = $this->getEntityCacheKey($id);
        $fromCache = true;
        $result    = false;

        $filters    = array(
            'id' => $id,
        );

        if (!empty($this->entityCacheDefaultFilters)) {
            $filters = ArrayUtils::mergeDistinct(
                $filters,
                $this->entityCacheDefaultFilters
            );
        }

        $getCall   = function() use ($id, $key, $filters) {
            return $this->get(
                $filters,
                null,
                $this->entityCacheDefaultIncludes
            );
        };

        if ($this->entityCacheEnabled) {
            $result = $this->getLockingCache()->get(
                $key,
                function() use (&$fromCache, $getCall) {
                    $fromCache = false;
                    return $getCall();
                },
                $this->entityCacheTtl
            );
        } else {
            $fromCache = false;
            $result    = $getCall();
        }

        if ($fromCache && false !== $result) {
            /**
             * Store inside entityCacheObjectStorage to be able to commit to
             * proper hydrators later on when changes need to be saved.
             */
            $this->entityCacheObjectStorage[$key] = serialize($result);
        }

        return $result;
    }

    /**
     * A cache key prefix based on the lass instance, default includes
     * and tracking identifiers
     *
     * @return string
     */
    protected function getCacheKeyPrefix()
    {
        $defaultFilterKeys = array_keys($this->entityCacheDefaultFilters);
        $cacheIdentificationKey = get_called_class() . '::';
        if (!empty($this->entityCacheDefaultIncludes)) {
            $cacheIdentificationKey .= 'include:' . strtolower(
                implode('|', $this->entityCacheDefaultIncludes)
            ) . ';';
        }
        if (!empty($this->entityCacheTrackedIdentifiers)) {
            $cacheIdentificationKey .= 'track:' . strtolower(
                implode('|', $this->entityCacheTrackedIdentifiers)
            ) . ';';
        }
        if (!empty($defaultFilterKeys)) {
            $cacheIdentificationKey .= 'filter:' . strtolower(
                implode('|', $defaultFilterKeys)
            ) . ';';
        }
        $crc = crc32($cacheIdentificationKey);
        // Convert to unsigned integer string
        sscanf($crc, "%u", $crc);
        return $crc;
    }

    /**
     * Generates a cache key for identifiers tracked for this cache
     *
     * @param  string $identifier
     * @param  string $identifierValue
     *
     * @return string
     */
    protected function getIdentifierCacheKeyForEntityIds($identifier, $identifierValue)
    {
        return $this->getCacheKeyPrefix() . '_ids_by_' . $identifier . '_' . $identifierValue;
    }

    /**
     * Generates a cache key for a given entity id
     *
     * @param  string $id
     *
     * @return string
     */
    protected function getEntityCacheKey($id)
    {
        return $this->getCacheKeyPrefix() . "_entity_{$id}";
    }

    /**
     * Fetch a collection of entities by a list of identifiers with the
     * given identifier type.
     *
     * @param string $identifier
     * @param array  $identifiers
     *
     * @return array<\ArrayIterator> like:
     * array(
     *     '12345' => <EntityCollection>,
     *     '23456' => <EntityCollection>,
     * ),
     */
    public function fetchEntityCollectionBy(
        $identifier,
        array $identifiers
    ) {
        if (!in_array($identifier, $this->entityCacheTrackedIdentifiers)) {
            throw new \Exception(
                'To use cached entities with differend identifiers ' .
                'the identifier "' . $identifier . '" key must ' .
                'be added to the tracked identifiers array!'
            );
        }
        $ids                 = array();
        $notFoundIdentifiers = array();
        if ($this->entityCacheEnabled && $identifier !== 'id') {
            $keys = array();
            $reverseKeys = array();
            foreach ($identifiers as $identifierValue) {
                $keys[$identifierValue] = $this
                ->getIdentifierCacheKeyForEntityIds(
                    $identifier,
                    $identifierValue
                );
                $reverseKeys[$keys[$identifierValue]] = $identifierValue;
            }
            $results = $this->getLockingCache()->getMultiple($keys);
            foreach ($keys as $key) {
                if (!isset($results[$key]) || false === $results[$key]) {
                    $notFoundIdentifiers[] = $reverseKeys[$key];
                    continue;
                }
                if (is_array($results[$key])) {
                    $ids = array_merge($ids, $results[$key]);
                } else {
                    $ids[] = $results[$key];
                }
            }
        } else {
            $notFoundIdentifiers = $identifiers;
        }

        if (!empty($notFoundIdentifiers)) {
            $filters = array(
                new Predicate\In($identifier, $notFoundIdentifiers)
            );

            if (!empty($this->entityCacheDefaultFilters)) {
                $filters = ArrayUtils::mergeDistinct(
                    $filters,
                    $this->entityCacheDefaultFilters
                );
            }
            $results = $this->search(
                null,
                $filters,
                null,
                null,
                null,
                array('id', $identifier),
                false
            );

            $toCache = array();
            foreach ($results as $result) {
                if ($this->entityCacheEnabled) {
                    $key = $this->getIdentifierCacheKeyForEntityIds(
                        $identifier,
                        $result[$identifier]
                    );
                    $toCache[$key][]  = $result['id'];
                }
                $ids[] = $result['id'];
            }

            if ($this->entityCacheEnabled && !empty($toCache)) {
                foreach ($toCache as $key => $data) {
                    if ($this->getLockingCache()->getLock($key)) {
                        $this->getLockingCache()->set(
                            $key,
                            $data,
                            $this->entityCacheTtl
                        );
                        $this->getLockingCache()->releaseLock($key);
                    }
                }
            }
        }

        $getIdentifier = StringUtils::underscoreToCamelCase("get_{$identifier}");
        $return        = array();
        $entities      = $this->fetchEntityCollectionByIds($ids);
        foreach ($entities as $entity) {
            $identifierValue = $entity->$getIdentifier();
            if (!isset($return[$identifierValue])) {
                $return[$identifierValue] = new \ArrayIterator();
            }
            $return[$identifierValue][] = $entity;
        }
        return $return;
    }

    /**
     * Fetch a list of entities from the database based on a list of primary
     * identifiers. When entity caching has been enabled on the mapper all
     * items will be stored within cache and saved to cache when modified.
     *
     * @param  array $ids
     * @return \ArrayIterator
     */
    public function fetchEntityCollectionByIds(array $ids)
    {
        $list        = array();
        $notFoundIds = array();

        if ($this->entityCacheEnabled) {
            $keys        = array();
            $reverseKeys = array();
            foreach ($ids as $id) {
                $keys[$id] = $this->getEntityCacheKey($id);
                $reverseKeys[$keys[$id]] = $id;
            }
            $results = $this->getLockingCache()->getMultiple($keys);
            foreach ($keys as $key) {
                if (!isset($results[$key]) || false === $results[$key]) {
                    $notFoundIds[] = $reverseKeys[$key];
                    continue;
                }
                $list[$results[$key]->getId()] = $results[$key];
                $this->entityCacheObjectStorage[$keys[$results[$key]->getId()]]
                    = serialize($results[$key]);
            }
        } else {
            $notFoundIds = $ids;
        }

        if (!empty($notFoundIds)) {
            $filters = array(
                new Predicate\In('id', $notFoundIds)
            );

            if (!empty($this->entityCacheDefaultFilters)) {
                $filters = ArrayUtils::mergeDistinct(
                    $filters,
                    $this->entityCacheDefaultFilters
                );
            }

            $entities = $this->search(
                array(
                    'begin' => 0,
                    'end'   => sizeof($notFoundIds)
                ),
                $filters,
                null,
                null,
                $this->entityCacheDefaultIncludes
            );
            foreach ($entities as $entity) {
                if ($this->entityCacheEnabled) {
                    $key = $this->getEntityCacheKey($entity->getId());
                    if ($this->getLockingCache()->getLock($key)) {
                        $this->getLockingCache()->set(
                            $key,
                            $entity,
                            $this->entityCacheTtl
                        );
                        $this->getLockingCache()->releaseLock($key);
                    }
                }
                $list[$entity->getId()] = $entity;
            }
        }
        return new \ArrayIterator($list);
    }

    /**
     * Attach to the entity_pre_save event triggered from the AbstractMapper
     * Here we have to check with the instance cache to make the AbstractMapper
     * aware of already committed changes
     */
    protected function attachToEntityPreSave()
    {
        $this->getEventManager()->attach(
            get_called_class() . '::entity_pre_save',
            function(Event $event) {
                $entity = $event->getParam('entity');
                if ($this->entityCacheEnabled && null !== $entity->getId()) {
                    $key = $this->getEntityCacheKey($entity->getId());
                    if (isset($this->entityCacheObjectStorage[$key])) {
                        ErrorHandler::start();
                        $original = unserialize($this->entityCacheObjectStorage[$key]);
                        ErrorHandler::stop(true);
                        $this->setChangesCommitted($original);
                        ObjectUtils::transferData($entity, $original);
                        return $original;
                    }
                }
            }
        );
    }

    /**
     * Attach to the entity_saved event triggered from the AbstractMapper
     * Here we can decide if and how to cache the specific entity
     */
    protected function attachToEntitySaved()
    {
        $this->getEventManager()->attach(
            get_called_class() . '::entity_saved',
            function(Event $event) {
                $entity   = $event->getParam('entity');
                $inserted = $event->getParam('inserted');
                if (
                    $this->entityCacheEnabled &&
                    $inserted &&
                    !empty($this->entityCacheTrackedIdentifiers)
                ) {
                    // Track new id's to known identifiers
                    foreach (
                        $this->entityCacheTrackedIdentifiers as $trackedIdentifier
                    ) {
                        $get = StringUtils::underscoreToCamelCase(
                            "get_{$trackedIdentifier}"
                        );
                        $value = $entity->$get();
                        if (null !== $value) {
                            $key = $this->getIdentifierCacheKeyForEntityIds(
                                $trackedIdentifier,
                                $value
                            );
                            $trackedIds = $this->getLockingCache()->get($key);
                            if (
                                is_array($trackedIds) &&
                                $this->getLockingCache()->getLock($key)
                            ) {
                                $trackedIds[] = $entity->getId();
                                $this->getLockingCache()->set($key, $trackedIds);
                                $this->getLockingCache()->releaseLock($key);
                            }
                        }
                    }
                }

                if (
                    $this->entityCacheEnabled &&
                    null !== $entity->getId()
                ) {
                    $key = $this->getEntityCacheKey($entity->getId());
                    // We always want to store into cache, this can become
                    // false depending on the following checks..
                    $storeIntoCache         = true;
                    /**
                     * We do want to add this entity to the instance cache
                     * if we re-save it within the same request this could
                     * come in handy.
                     */
                    $storeIntoInstanceCache = true;
                    if (
                        !isset($this->entityCacheObjectStorage[$key]) &&
                        !empty($this->entityCacheDefaultFilters)
                    ) {
                        /**
                         * Don't store because we did not load this entity
                         * yet using the entity cache.. we don't know if this
                         * entity matches our default entity cache filters
                         */
                        $storeIntoCache = false;
                    } elseif (
                        isset($this->entityCacheObjectStorage[$key]) &&
                        !empty($this->entityCacheDefaultFilters)
                    ) {
                        /**
                         * We need to verify if any of the default filtered
                         * columns has been modified in comparison with the
                         * data already available within cache. If so the
                         * entity must be removed from cache for the filters
                         * to re-validate the entity.
                         */
                        ErrorHandler::start();
                        $original          = unserialize(
                            $this->entityCacheObjectStorage[$key]
                        );
                        ErrorHandler::stop(true);
                        $defaultFilterKeys = array_keys(
                            $this->entityCacheDefaultFilters
                        );
                        foreach ($defaultFilterKeys as $filterKey) {
                            $methodGet = StringUtils::underscoreToCamelCase(
                                "get_{$filterKey}"
                            );
                            if ($original->$methodGet() !== $entity->$methodGet()) {
                                $storeIntoCache = false;
                            }
                        }
                    }
                    /**
                     * When default entity includes have been configured
                     * the entity should only be stored within cache when all of
                     * these includes have been set. Normally when loading through
                     * cache this is always the case, however when new entities
                     * get added this is not always true.
                     */
                    foreach ($this->entityCacheDefaultIncludes as $defaultInclude) {
                        $getInclude = StringUtils::underscoreToCamelCase(
                            "get_{$defaultInclude}"
                        );
                        if (null === $entity->$getInclude()) {
                            $storeIntoCache = false;
                        }
                    }
                    if (
                        $storeIntoCache &&
                        $this->getLockingCache()->getLock($key)
                    ) {
                        $this->getLockingCache()->set(
                            $key,
                            $entity,
                            $this->entityCacheTtl
                        );
                        $this->getLockingCache()->releaseLock($key);
                    }
                    if ($storeIntoInstanceCache) {
                        $this->entityCacheObjectStorage[$key] = serialize($entity);
                    }

                    if (
                        false === $storeIntoCache &&
                        $this->getLockingCache()->getLock($key)
                    ) {
                        // We must check and unset previous data from cache..
                        $this->getLockingCache()->del($key);
                        $this->getLockingCache()->releaseLock($key);
                    }
                    if (false === $storeIntoInstanceCache) {
                        if (isset($this->entityCacheObjectStorage[$key])) {
                            unset($this->entityCacheObjectStorage[$key]);
                        }
                    }
                }
            }
        );
    }
}
