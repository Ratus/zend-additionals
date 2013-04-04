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
    protected $entityCacheEnabled = false;

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
            $keys = array();
            foreach ($ids as $id) {
                $keys[$id] = "{$keyPrefix}{$id}";
            }
            $results = $this->getLockingCache()->getMultiple($keys);
            foreach ($results as $key => $result) {
                if (!is_object($result)) {
                    $notFoundIds[] = $result;
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
