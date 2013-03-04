<?php
namespace ZendAdditionals\ServiceManager;

use ZendAdditionals\View\Helper\Widget\AbstractWidget;
use ZendAdditionals\StdLib\ArrayUtils;
use Zend\View\HelperPluginManager;

/**
 * @category    ZendAdditionals
 * @package     ServiceManager
 */
class FactoryContainer
{
    /**
     * Get a widget instance
     *
     * @param HelperPluginManager $hpm
     * @param AbstractWidget      $widget
     * @param string              $configNamespace Dotted seperaed string to the widget config
     *
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
}
