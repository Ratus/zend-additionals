<?php

namespace ZendAdditionals\Db\ResultSet;

class JoinedHydratingResultSet extends \Zend\Db\ResultSet\HydratingResultSet
{
    protected $objectProtoTypes = array();

    /**
     * Set a list of object prototypes to be able to hydrate the joined
     * statement.
     *
     * @param array<object> $protoTypes
     * @return \DatingProfile\JoinedHydratingResultSet
     */
    public function setObjectPrototypes(array $protoTypes)
    {
        $this->objectProtoTypes = $protoTypes;
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

        if (!empty($this->objectProtoTypes)) {
            foreach($this->objectProtoTypes as $key => $protoType) {
                $dataJoin = $this->pregGrepKeys('/^'.preg_quote($key).'\./', $data);
                $data = array_diff_key($data, $dataJoin);
                $entityData = array();
                foreach ($dataJoin as $dataKey => $dataValue) {
                    $entityData[substr($dataKey, strpos($dataKey, '.') + 1)] = $dataValue;
                }
                $setCall = $transform('set_' . $key);
                $entitiesToInject[$setCall] =$this->hydrator->hydrate($entityData, $protoType);
            }
        }
        $entity = $this->hydrator->hydrate($data, $object);

        foreach ($entitiesToInject as $methodCall => $entityToInject) {
            $entity->$methodCall($entityToInject);
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

