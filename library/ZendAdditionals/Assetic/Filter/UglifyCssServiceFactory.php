<?php
namespace ZendAdditionals\Assetic\Filter;

use Assetic\Filter\UglifyCssFilter;
use ZendAdditionals\Stdlib\ArrayUtils;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * @category    ZendAdditionals
 * @package     Assetic
 * @subpackage  Filter
 */
class UglifyCssServiceFactory implements FactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        // Get config
        $config    = $serviceLocator->get('config');

        // Config namespace
        $ns = 'assetic.filters.uglifycss.';

        // Constructor params
        $nodeBin   = ArrayUtils::arrayTarget('assetic.config.node_bin', $config, null);
        $uglifyBin = ArrayUtils::arrayTarget($ns . 'bin', $config, '/usr/bin/uglifycss');

        // Properties
        $expandVars   =  ArrayUtils::arrayTarget($ns . 'expand_vars', $config, false);
        $uglyComments =  ArrayUtils::arrayTarget($ns . 'ugly_comments', $config, false);
        $cuteComments =  ArrayUtils::arrayTarget($ns . 'cute_comments', $config, false);

        // Create filter
        $filter = new UglifyCssFilter($uglifyBin, $nodeBin);
        $filter->setExpandVars($expandVars);
        $filter->setUglyComments($uglyComments);
        $filter->setCuteComments($cuteComments);

        return $filter;
    }
}
