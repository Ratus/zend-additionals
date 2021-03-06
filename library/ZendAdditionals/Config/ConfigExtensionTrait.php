<?php
namespace ZendAdditionals\Config;

use ZendAdditionals\Stdlib\ArrayUtils;

/**
 * Get a config item based on dotted string
 *
 * @category    ZendAdditionals
 * @package     Config
 */
trait ConfigExtensionTrait
{
    /**
     * {@inheritdoc}
     */
    abstract public function getServiceLocator();

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->getServiceLocator()->get('Config');
    }

    /**
     * Get an item from the config file
     *
     * @param string $needle Dot separated string with the path you want
     * @param mixed $default The default value when the item has not be found
     * @example daemonizer.locations.pids => $config['daemonizer']['locations']['pids']
     *
     * @return mixed The value from the config | mixed on not found
     */
    protected function getConfigItem($needle, $default = null)
    {
        return ArrayUtils::arrayTarget(
            $needle,
            $this->getConfig(),
            $default
        );
    }
}
