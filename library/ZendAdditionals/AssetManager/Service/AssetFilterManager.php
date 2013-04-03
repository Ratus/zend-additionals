<?php
namespace ZendAdditionals\AssetManager\Service;

use AssetManager\Exception;
use Assetic\Asset\AssetInterface;

class AssetFilterManager extends \AssetManager\Service\AssetFilterManager
{
    /**
     * See if there are filters for the asset, and if so, set them.
     *
     * @param   string          $path
     * @param   AssetInterface  $asset
     */
    public function setFilters($path, AssetInterface $asset)
    {
        $config = $this->getConfig();

        if (!empty($config[$path])) {
            $filters = $config[$path];
        } elseif (!empty($config[$asset->mimetype])) {
            $filters = $config[$asset->mimetype];
        } else {
            $extension = $this->getMimeResolver()->getExtension($asset->mimetype);
            if (!empty($config[$extension])) {
                $filters = $config[$extension];
            } else {
                return;
            }
        }

        $settings = array(
            'merge_before_filter' => false
        );

        if (isset($filters['settings'])) {
            $settings = $filters['settings'];
            $filters  = $filters['filters'];
        }

        if ($settings['merge_before_filter']) {
            $newAsset = new \Assetic\Asset\StringAsset($asset->dump());
            $asset->clear();
            $asset->add($newAsset);
        }

        foreach ($filters as $filter) {
            if (!empty($filter['filter'])) {
                $this->ensureByFilter($asset, $filter['filter']);
            } elseif(!empty($filter['service'])) {
                $this->ensureByService($asset, $filter['service']);
            } else {
                throw new Exception\RuntimeException(
                    'Invalid filter supplied. Expected Filter or Service.'
                );
            }
        }
    }
}
