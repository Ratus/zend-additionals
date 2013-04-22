<?php
namespace ZendAdditionals\ServiceManager;

use Zend\ServiceManager\ServiceLocatorInterface;
use ZendAdditionals\Db\Adapter\MasterSlaveAdapter;
use ZendAdditionals\View\Helper\Widget\AbstractWidget;
use ZendAdditionals\Stdlib\ArrayUtils;
use Zend\View\HelperPluginManager;
use Zend\Db\Adapter\Adapter;
use \PDO;

/**
 * @category    ZendAdditionals
 * @package     ServiceManager
 */
class FactoryContainer
{
    /**
     * Get a widget instance
     *
     * @param  HelperPluginManager $hpm
     * @param  AbstractWidget      $widget
     * @param  string              $configNamespace Dotted seperaed string to the widget config
     * @return AbstractWidget
     */
    protected static function widgetViewHelper(
        HelperPluginManager $helperPluginManager,
        AbstractWidget $widget,
        $configNamespace = null
    ) {
        $serviceLocator = $helperPluginManager->getServiceLocator();
        $config         = $serviceLocator->get('Config');

        $widget->setConfig(ArrayUtils::arrayTarget(
            $configNamespace,
            $config,
            array()
        ));

        return $widget;
    }

    /**
     * @param  ServiceLocatorInterface $locator
     * @param  string                  $databaseName
     * @return MasterSlaveAdapter
     */
    protected static function createMasterSlaveAdapter(ServiceLocatorInterface $locator, $databaseName)
    {
        /** @var $balancer \MyBalancer\MyBalancer */
        $balancer = $locator->get('my_balancer');

        $bestAvailable = $balancer->getBestAvailableServers($databaseName);
        if ($bestAvailable === false) {
            throw new \Zend\ServiceManager\Exception\ServiceNotCreatedException(
                'Could not find available server for ' . $databaseName
            );
        }

        $masterInfo = $balancer->parseConnectionUrl($bestAvailable['master']);
        $slaveInfo  = $balancer->parseConnectionUrl($bestAvailable['slave']);

        $slaveAdapter = new Adapter(
            array(
                'driver'         => 'pdo',
                'dsn'            => "{$slaveInfo['scheme']}:dbname={$slaveInfo['database']};host={$slaveInfo['host']}",
                'username'       => $slaveInfo['user'],
                'password'       => $slaveInfo['pass'],
                'driver_options' => array(
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8', time_zone = 'UTC'"
                ),
            )
        );

        $masterSlaveAdapter = new MasterSlaveAdapter(
            $slaveAdapter,
            array(
                'driver'         => 'pdo',
                'dsn'            => "{$masterInfo['scheme']}:dbname={$masterInfo['database']};host={$masterInfo['host']}",
                'username'       => $masterInfo['user'],
                'password'       => $masterInfo['pass'],
                'driver_options' => array(
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8', time_zone = 'UTC'"
                ),
            )
        );

        return $masterSlaveAdapter;
    }
}
