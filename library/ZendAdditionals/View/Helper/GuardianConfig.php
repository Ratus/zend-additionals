<?php
namespace ZendAdditionals\View\Helper;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\View\Helper\AbstractHelper;
use Zend\ServiceManager\ServiceLocatorInterface;


/**
 * Helper for guardian config
 */
class GuardianConfig extends AbstractHelper implements ServiceLocatorAwareInterface
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
     * Get guardian config
     *
     * @return guardian config
    */
    public function __invoke() {
        $config = $this->getServiceLocator()->get('config');
        return $config['guardian'];
    }
}