<?php
namespace ZendAdditionals\Config;

/**
 * Get a config item based on dotted string
 *
 * @category    ZendAdditionals
 * @package     Config
 */
trait ConfigExtension
{
    /**
     * {@inheritdoc}
     */
    abstract public function getServiceLocator();

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
        $parts = explode('.', $needle);

        $value = $this->getServiceLocator()->get('Config');

        foreach ($parts as $part) {
            if (array_key_exists($part, $value) === false) {
                return $default;
            }

            $value = $value[$part];
        }

        return $value;
    }
}