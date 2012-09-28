<?php
namespace ZendAdditionals\Db\ResultSet;

use ZendAdditionals\Db\EntityAssociation\EntityAssociation;

class JoinedHydratingResultSet extends \Zend\Db\ResultSet\HydratingResultSet
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

    public function current()
    {
        $data = $this->dataSource->current();
        $object = clone $this->objectPrototype;
        if (!is_array($data)) {
            return false;
        }
        $entitiesToInject = array();

        $transform = new \Zend\Filter\Word\UnderscoreToCamelCase();

        $entities = array();

        if (!empty($this->associations)) {
            foreach($this->associations as $association) { /* @var $association EntityAssociation */
                $dataJoin = $this->pregGrepKeys('/^'.preg_quote($association->getAlias()).'__/', $data);
                $entityData = array();
                foreach ($dataJoin as $dataKey => $dataValue) {
                    $entityData[substr($dataKey, strrpos($dataKey, '__') + 1)] = $dataValue;
                }
                $setCall = 'set' . $transform($association->getEntityIdentifier());
                $prototype = clone $association->getPrototype();
                $entities[$association->getAlias()] = $this->hydrator->hydrate($entityData, $prototype);

                $entitiesToInject[] = array(
                    'set_call' => $setCall,
                    'parent_alias' => $association->getParentAlias() ?: 'base',
                    'alias' => $association->getAlias(),
                );
            }
        }
        $entities['base'] = $this->hydrator->hydrate($data, $object);
        foreach ($entitiesToInject as $aliasInfo) {
            $entities[$aliasInfo['parent_alias']]->$aliasInfo['set_call']($entities[$aliasInfo['alias']]);
        }

        return $this->hydrator->hydrate($data, $object);
    }

    /**
     * Grep elements from an array based on keys
     *
     * @param string $pattern The regular expression you want to match on
     * @param array $input The array you want to filter on
     *
     * @return array The filtered input array
     */
    public function pregGrepKeys($pattern, $input, $keyReplacePattern = null, $keyReplaceValue = null)
    {
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
}

