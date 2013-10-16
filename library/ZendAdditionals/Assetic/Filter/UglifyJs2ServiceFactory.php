<?php
namespace ZendAdditionals\Assetic\Filter;

use Assetic\Filter\UglifyJs2Filter;
use ZendAdditionals\Stdlib\ArrayUtils;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * @category    ZendAdditionals
 * @package     Assetic
 * @subpackage  Filter
 */
class UglifyJs2ServiceFactory implements FactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        // Get config
        $config    = $serviceLocator->get('config');

        // Config namespace
        $ns = 'assetic.filters.uglifyjs2.';

        // Constructor params
        $nodeBin   = ArrayUtils::arrayTarget('assetic.config.node_bin', $config, null);
        $uglifyBin = ArrayUtils::arrayTarget($ns . 'bin', $config, '/usr/bin/uglifyjs');

        // Properties
        $compress =  ArrayUtils::arrayTarget($ns . 'compress', $config, false);
        $beautify =  ArrayUtils::arrayTarget($ns . 'beautify', $config, false);
        $mangle   =  ArrayUtils::arrayTarget($ns . 'mangle', $config, false);
        $screwIe8 =  ArrayUtils::arrayTarget($ns . 'screwIe8', $config, false);
        $comments =  ArrayUtils::arrayTarget($ns . 'comments', $config, false);
        $wrap     =  ArrayUtils::arrayTarget($ns . 'wrap', $config, false);

        // Create filter
        $filter = new UglifyJs2Filter($uglifyBin, $nodeBin);
        $filter->setCompress($compress);
        $filter->setBeautify($beautify);
        $filter->setMangle($mangle);
        $filter->setScrewIe8($screwIe8);
        $filter->setComments($comments);
        $filter->setWrap($wrap);

        return $filter;
    }
}
