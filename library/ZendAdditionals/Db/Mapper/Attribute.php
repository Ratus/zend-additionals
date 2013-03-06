<?php
namespace ZendAdditionals\Db\Mapper;

class Attribute extends AbstractMapper
{
    const SERVICE_NAME = 'ZendAdditionals\Db\Mapper\Attribute';

    protected $tableName           = 'attribute';
    protected $autoGenerated       = 'id';
    protected $tablePrefixRequired = true;

    protected $cachedAttributeData = null;

    protected $primaries = array(
        array('id'),
    );

    protected function initializeCachedAttributes($tablePrefix)
    {
        if (
            is_array($this->cachedAttributeData) &&
            isset($this->cachedAttributeData[$tablePrefix])
        ) {
            return;
        }
        $lockingCache = $this->getServiceManager()->get('ZendAdditionals\Service\LockingCache');
        /*@var $lockingCache \ZendAdditionals\Cache\Pattern\LockingCache*/

        $class = $this;

        $this->cachedAttributeData[$tablePrefix] = $lockingCache->get(
            __CLASS__ . '::' . $tablePrefix,
            function() use ($tablePrefix, $class) {
                return $class->getAllAttributes($tablePrefix);
            },
            25200
        );
        foreach ($this->cachedAttributeData[$tablePrefix]['by_label'] as $attribute) {
            $this->getHydrator()->setChangesCommitted($attribute);
        }
    }

    /**
     * This method should only get called from initializeCachedAttributes
     *
     * @param string $tablePrefix
     *
     * @return array like
     * array(
     *     'by_label' => array<Attribute>,
     *     'by_id'    => array<Attribute>,
     * )
     */
    public function getAllAttributes($tablePrefix)
    {
        $attributes = array();
        $select = $this->getSelect($tablePrefix . $this->getTableName());
        $res = $this->getAll($select);

        foreach ($res as $attributeEntity) {
            $attributes['by_label'][$attributeEntity->getLabel()] = $attributeEntity;
            $attributes['by_id'][$attributeEntity->getId()] = $attributeEntity;
        }

        return $attributes;
    }

    public function getIdByLabel($label, $tablePrefix)
    {
        $this->initializeCachedAttributes($tablePrefix);
        if (!isset($this->cachedAttributeData[$tablePrefix]['by_label'][$label])) {
            throw new \UnexpectedValueException(
                'The expected attribute identified by label: ' . $label .
                ' does not exist!'
            );
        }
        return $this->cachedAttributeData[$tablePrefix]['by_label'][$label]->getId();
    }

    public function getAttributeByLabel($label, $tablePrefix)
    {
        $this->initializeCachedAttributes($tablePrefix);
        if (!isset($this->cachedAttributeData[$tablePrefix]['by_label'][$label])) {
            throw new \UnexpectedValueException(
                'The expected attribute identified by label: ' . $label .
                ' does not exist!'
            );
        }
        return $this->cachedAttributeData[$tablePrefix]['by_label'][$label];
    }

    public function getAttributeById($id, $tablePrefix)
    {
        $this->initializeCachedAttributes($tablePrefix);
        if (!isset($this->cachedAttributeData[$tablePrefix]['by_id'][$id])) {
            throw new \UnexpectedValueException(
                'The expeted attribute identified by id: ' . $id .
                ' does not exist!'
            );
        }
        return $this->cachedAttributeData[$tablePrefix]['by_id'][$id];
    }

    protected function getAllowFilters()
    {
        return true;
    }
}

