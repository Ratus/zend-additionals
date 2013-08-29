<?php
namespace ZendAdditionals\View\Helper;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\View\Helper\AbstractHelper;
use Zend\ServiceManager\ServiceLocatorInterface;


/**
 * Helper for application config
 */
class ApplicationConfig extends AbstractHelper implements ServiceLocatorAwareInterface
{
    use \ZendAdditionals\Config\ConfigExtensionTrait;
    use \Zend\ServiceManager\ServiceLocatorAwareTrait;

    protected $pluginManager;

    /**
     * Get service locator
     *
     * @return ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        $locator = $this->serviceLocator;
        if ($locator instanceof \Zend\ServiceManager\AbstractPluginManager) {
            $this->pluginManager = $locator;
            return $locator->getServiceLocator();
        }
        return $locator;
    }

    /**
     * Get application config
     *
     * @return application config
    */
    public function __invoke() {
        return $this->getServiceLocator()->get('config');
    }
}