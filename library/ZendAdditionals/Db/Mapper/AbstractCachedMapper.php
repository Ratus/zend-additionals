<?php
namespace ZendAdditionals\Db\Mapper;

use ZendAdditionals\Cache\LockingCacheAwareInterface;
use ZendAdditionals\Cache\LockingCacheAwareTrait;
use Zend\Db\Sql\Predicate;
use ZendAdditionals\Stdlib\ObjectUtils;

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
     *
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
     * Fetch a specific entity by the primary identifier. When entity caching
     * has been enabled on the mapper all items will be stored within cache
     * and saved to cache when modified.
     *
     * @param  integer $id
     * @return object
     */
    public function fetchEntityById($id)
    {
        $key       = get_called_class() . "_entity_{$id}";
        $fromCache = true;
        $result    = false;
        $getCall   = function() use ($id) {
            return $this->get(
                    array(
                    'id' => $id,
                    ), null, $this->entityCacheDefaultIncludes
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
     * Fetch a collection of entities by a list of identifiers with the
     * given identifier type.
     *
     * @param string $identifier
     * @param array  $identifiers
     * 
     * @return \ArrayIterator
     */
    public function fetchEntityCollectionBy(
        $identifier,
        array $identifiers
    ) {
        $ids                 = array();
        $notFoundIdentifiers = array();
        $keyPrefix           = get_called_class() .
                               '_ids_by_' . $identifier . '_';

        if ($this->entityCacheEnabled && $identifier !== 'id') {
            $keys = array();
            $reverseKeys = array();
            foreach ($identifiers as $identifierValue) {
                $keys[$identifierValue] = "{$keyPrefix}{$identifierValue}";
                $reverseKeys["{$keyPrefix}{$identifierValue}"]
                                        = $identifierValue;
            }
            $results = $this->getLockingCache()->getMultiple($keys);
            foreach ($results as $key => $result) {
                if (false === $result) {
                    $notFoundIdentifiers[] = $reverseKeys[$key];
                    continue;
                }
                if (is_array($result)) {
                    $ids = array_merge($ids, $result);
                } else {
                    $ids[] = $result;
                }
            }
        } else {
            $notFoundIdentifiers = $identifiers;
        }

        if (!empty($notFoundIdentifiers)) {
            $results = $this->search(
                null,
                array(
                    new Predicate\In($identifier, $notFoundIdentifiers)
                ),
                null,
                null,
                null,
                array('id', $identifier),
                false
            );

            $toCache = array();
            foreach ($results as $result) {
                if ($this->entityCacheEnabled) {
                    $key = "{$keyPrefix}{$result[$identifier]}";
                    if (isset($toCache[$key])) {
                        $toCache[$key] = array(
                            $toCache[$key],
                            $result['id'],
                        );
                    } else {
                        $toCache[$key] = $result['id'];
                    }
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

        $transform = function ($letters) {
            return strtoupper($letters[1]);
        };
        $getIdentifier = preg_replace_callback(
            '/_([a-z])/',
            $transform,
            "get_{$identifier}"
        );

        $return = array();

        $entities = $this->fetchEntityCollectionByIds($ids);
        foreach ($entities as $entity) {
            $identifierValue = $entity->$getIdentifier();
            if (!isset($return[$identifierValue])) {
                $return[$identifierValue] = $entity;
            } elseif (is_array($return[$identifierValue])) {
                $return[$identifierValue][] = $entity;
            } else {
                $return[$identifierValue] = new \ArrayIterator(
                    array(
                        $return[$identifierValue],
                        $entity
                    )
                );
            }
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
        $list = array_flip($ids);
        $notFoundIds = array();

        if ($this->entityCacheEnabled) {
            $keyPrefix = get_called_class() . '_entity_';
            $keys        = array();
            $reverseKeys = array();
            foreach ($ids as $id) {
                $keys[$id] = "{$keyPrefix}{$id}";
                $reverseKeys["{$keyPrefix}{$id}"] = $id;
            }
            $results = $this->getLockingCache()->getMultiple($keys);
            foreach ($results as $key => $result) {
                if (false === $result) {
                    $notFoundIds[] = $reverseKeys[$key];
                    continue;
                }
                $list[$result->getId()] = $result;
                $this->entityCacheObjectStorage[$keys[$result->getId()]] = serialize($result);
            }
        } else {
            $notFoundIds = $ids;
        }

        if (!empty($notFoundIds)) {
            $entities = $this->search(
                array(
                    'begin' => 0,
                    'end'   => sizeof($notFoundIds)
                ),
                array(
                    new Predicate\In('id', $notFoundIds)
                ),
                null,
                null,
                $this->entityCacheDefaultIncludes
            );
            foreach ($entities as $entity) {
                if ($this->entityCacheEnabled) {
                    $key = "{$keyPrefix}{$entity->getId()}";
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
     * When entity caching has been enabled on the mapper all
     * items will be stored within cache and saved to cache when modified.
     *
     * {@inheritDoc}
     */
    public function save(
              $entity,
              $tablePrefix        = null,
        array $parentRelationInfo = null,
              $useTransaction     = true
    ) {
        if ($this->entityCacheEnabled) {
            $key = get_called_class() . "_entity_{$entity->getId()}";
            if (isset($this->entityCacheObjectStorage[$key])) {
                $original = unserialize($this->entityCacheObjectStorage[$key]);
                $this->setChangesCommitted($original);
                ObjectUtils::transferData($entity, $original);
                $entity = &$original;
            }
        }
        $result = parent::save(
            $entity,
            $tablePrefix,
            $parentRelationInfo,
            $useTransaction
        );
        if (
            false !== $result &&
            $this->entityCacheEnabled &&
            $this->getLockingCache()->getLock($key)
        ) {
            $this->getLockingCache()->set(
                $key,
                $entity,
                $this->entityCacheTtl
            );
            $this->getLockingCache()->releaseLock($key);
        }
        return $result;
    }
}
