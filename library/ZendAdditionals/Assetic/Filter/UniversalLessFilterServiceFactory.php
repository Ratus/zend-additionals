<?php
namespace ZendAdditionals\Assetic\Filter;

use Assetic\Filter;
use ZendAdditionals\Stdlib\ArrayUtils;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class UniversalLessFilterServiceFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $locator)
    {
        $config    = $locator->get('Config');

        $useFallback = ArrayUtils::arrayTarget(
            'asset_manager.settings.less.use_php_fallback',
            $config,
            true
        );

        if ($useFallback && strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            error_log(
                'Less PHP will be deprecated due to limitation. Setup your node.js environment',
                E_USER_DEPRECATED
            );

            include __DIR__ . '/lib/lessc.inc.php';

            $filter = new Filter\LessphpFilter();
        } else {
            // Node bin location
            $nodeBin = ArrayUtils::arrayTarget(
                'asset_manager.settings.less.node_bin',
                $config,
                null
            );

            // Node include paths
            $nodePaths = ArrayUtils::arrayTarget(
                'asset_manager.settings.less.node_paths',
                $config,
                array()
            );

            $filter = new Filter\LessFilter($nodeBin, $nodePaths);
        }

        return new UniversalLessFilter(
            $filter,
            ArrayUtils::arrayTarget('asset_manager.less_vars', $config, array())
        );
    }
}