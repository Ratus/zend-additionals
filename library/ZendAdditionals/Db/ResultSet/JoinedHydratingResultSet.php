<?php
namespace ZendAdditionals\Db\ResultSet;

use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\EventManager;

use ZendAdditionals\Db\EntityAssociation\EntityAssociation;
use ZendAdditionals\Db\Entity;

class JoinedHydratingResultSet extends \Zend\Db\ResultSet\HydratingResultSet implements
    EventManagerAwareInterface
{
    protected $associations = array();

    /**
     * Set a list of object prototypes to be able to hydrate the joined
     * statement.
     *
     * @param array<object> $protoTypes
     * @return \DatingProfile\JoinedHydratingResultSet
     */
    public function setAssociations(array $associations)
    {
        $this->associations = $associations;
        return $this;
    }

    /**
     * Get the current record from the resultset
     *
     * @param boolean $returnEntity When FALSE the result will be an array
     *
     * @return object|array|FALSE
     */
    public function current($returnEntity = true)
    {
        $data = $this->dataSource->current();

        // Return false when no data has been found
        if (!is_array($data)) {
            return false;
        }

        $object             = clone $this->objectPrototype;
        $objectClassName    = get_class($object);
        $entitiesToInject   = array();
        $transform          = new \Zend\Filter\Word\UnderscoreToCamelCase();
        $entities           = array();
        $entityClasses      = array(
            'base' => $objectClassName
        );

        // Create Event Container for the event triggers
        $eventContainer     = new Entity\EventContainer();

        if ($returnEntity) {
            $eventContainer->setHydrateType(Entity\EventContainer::HYDRATE_TYPE_OBJECT);
        } else {
            $eventContainer->setHydrateType(Entity\EventContainer::HYDRATE_TYPE_ARRAY);
        }

        if (empty($this->associations) === false) {
            foreach($this->associations as $association) {
                /* @var $association EntityAssociation */
                $alias           = $association->getAlias();
                $entityData      = $this->shiftJoinData($alias, $data);
                $entityClassName = get_class($association->getPrototype());

                // Save classname for the data injection phase
                $entityClasses[$alias] = $entityClassName;

                // Fill the event container
                $eventContainer->setData($entityData);
                $eventContainer->setEntityClassName($entityClassName);
                unset($entityData);

                // Trigger preHydrate event
                $this->getEventManager()->trigger('preHydrate', $this, $eventContainer);

                // Call hydrator only when returning entities
                if ($returnEntity) {
                    $prototype = clone $association->getPrototype();
                    
                    // Hydrate the data
                    $eventContainer->setData(
                        $this->hydrator->hydrate(
                            $eventContainer->getData(),
                            $prototype
                        )
                    );
                }

                // Trigger postHydrate event
                $this->getEventManager()->trigger('postHydrate', $this, $eventContainer);

                // Set the hydrated results to the entities array
                $entities[$alias] = $eventContainer->getData();
                $eventContainer->setData(null);

                // Set references for injecting entities after hydrating main object
                $entitiesToInject[] = $association;
            }
        }

        // Set eventdata for main object
        $eventContainer->setEntityClassName($objectClassName);
        $eventContainer->setData($data);
        unset($data);

        // Trigger preHydrate for main object
        $this->getEventManager()->trigger('preHydrate', $this, $eventContainer);

        if ($returnEntity) {
            // Hydrate main object
            $eventContainer->setData(
                $this->hydrator->hydrate(
                    $eventContainer->getData(),
                    $object
                )
            );
        }

        // Trigger postHydrate for main object
        $this->getEventManager()->trigger('postHydrate', $this, $eventContainer);

        // Set the base to entities array
        $entities['base'] = $eventContainer->getData();
        $eventContainer->setData(null);

        // Inject the entities to the main object
        foreach ($entitiesToInject as $association) {
            /** @var EntityAssociation */
            $parentAlias        = $association->getParentAlias() ?: 'base';
            $alias              = $association->getAlias();
            $entityIdentifier   = $association->getEntityIdentifier();
            $setCall            = 'set'.$transform($entityIdentifier);

            // Set event parameters
            $eventParams = array(
                'entity' => $entities[$parentAlias],
                'value'  => $entities[$alias],
                'call'   => $setCall,
            );

            // Set parameters in the eventContainer
            $eventContainer->setData($eventParams);
            $eventContainer->setEntityClassName($entityClasses[$parentAlias]);

            // We can unset data when we're im array mode
            if ($returnEntity === false) {
                unset($eventParams);
            }


            // Trigger preInjectEntity
            $this->getEventManager()->trigger('preInjectEntity', $this, $eventContainer);

            // Set eventParams when in array mode
            if ($returnEntity === false) {
                $eventParams = $eventContainer->getData();

                // Overwrite entities
                $entities[$parentAlias] = $eventParams['entity'];
                $entities[$alias]       = $eventParams['value'];

                $entities[$parentAlias][$entityIdentifier] = $entities[$alias];

                // Rebuild eventParams for array mode
                $eventParams = array(
                    'entity' => $entities[$parentAlias],
                    'value'  => $entities[$alias],
                    'call'   => $setCall,
                );

                $eventContainer->setData($eventParams);
                unset($eventParams);
            } else {
                // Inject the data via OOP
                $entities[$parentAlias]->$setCall($entities[$alias]);
            }

            // Trigger postInjectEntity
            $this->getEventManager()->trigger('postInjectEntity', $this, $eventContainer);

             // Set eventParams when in array mode
            if ($returnEntity === false) {
                $eventParams = $eventContainer->getData();

                // Overwrite entities
                $entities[$parentAlias] = $eventParams['entity'];
                $entities[$alias]       = $eventParams['value'];
            }
        }

        return $entities['base'];
    }

    protected function shiftJoinData($alias, &$data, $delimiter = '__')
    {
        $needle     = preg_quote($alias.$delimiter);
        $dataJoin   = $this->pregGrepKeys("/^{$needle}/", $data);

        $data = array_diff_key($data, $dataJoin);

        $joinData = array();

        foreach ($dataJoin as $dataKey => $dataValue) {
            $newDataKey = substr($dataKey, strrpos($dataKey, $delimiter) + 2);
            $joinData[$newDataKey] = $dataValue;
            unset($dataJoin[$dataKey]);
        }

        return $joinData;
    }

    /**
     * Grep elements from an array based on keys
     *
     * @param string $pattern The regular expression you want to match on
     * @param array $input The array you want to filter on
     *
     * @return array The filtered input array
     */
    public function pregGrepKeys(
        $pattern,
        $input,
        $keyReplacePattern = null,
        $keyReplaceValue = null
    ) {
        $keys   = array_keys($input);
        $values = array_values($input);
        $input  = array();

        $keys = preg_grep($pattern, $keys);

        foreach ($keys as $key => $value) {
            if (!empty($keyReplacePattern)) {
                $value = preg_replace($keyReplacePattern, $keyReplaceValue, $value);
            }
            $input[$value] = $values[$key];
        }

        return $input;
    }

    /**
     * Set the event manager
     *
     * @param EventManagerInterface $eventManager
     *
     * @return JoinedHydratingResultSet
     */
    public function setEventManager(EventManagerInterface $eventManager)
    {
        $this->eventManager = $eventManager;
        return $this;
    }

    /**
     * Get the event manager
     *
     * @return EventManager
     */
    public function getEventManager()
    {
        return $this->eventManager;
    }
}

