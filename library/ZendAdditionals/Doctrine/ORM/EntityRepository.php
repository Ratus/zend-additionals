<?php
namespace ZendAdditionals\Doctrine\ORM;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Parameter;

/**
 * @category    ZendAdditionals
 * @package     Doctrine
 * @package     ORM
 */
class EntityRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * Count the amount of rows
     *
     * @param array $predicates An array of predicates array(fieldName => value)
     * @return integer
     */
    public function count($predicates = array())
    {
        $identifier = $this->getClassMetadata()->getIdentifier();
        $identifier = array_shift($identifier);

        $table = $this->getClassName();
        $alias = strtolower($table[0]);

        $where = '';
        $parameters = new ArrayCollection();
        if (empty($predicates) === false) {
            $where .= ' WHERE ';

            foreach ($predicates as $fieldName => $value) {
                $type   = $this->getClassMetadata()->getTypeOfField($fieldName);
                $where .= "{$alias}.{$fieldName} = :{$fieldName}";

                // Add to parameter list
                $parameters->add(new Parameter($fieldName, $value, $type));
            }
        }

        $result = $this->getEntityManager()->createQuery(
            "SELECT COUNT({$alias}.{$identifier}) FROM {$table} {$alias}{$where}"
        )
        ->setParameters($parameters)
        ->getSingleScalarResult();

        return (int) $result;
    }
}
