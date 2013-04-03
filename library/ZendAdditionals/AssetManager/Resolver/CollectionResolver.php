<?php
namespace ZendAdditionals\AssetManager\Resolver;

use Assetic\Asset\AssetInterface;
use AssetManager\Exception;
use ZendAdditionals\Assetic\Asset\AssetCollection;

class CollectionResolver extends \AssetManager\Resolver\CollectionResolver
{
    /**
     * {@inheritDoc}
     */
    public function resolve($name)
    {
        if (!isset($this->collections[$name])) {
            return null;
        }

        if (!is_array($this->collections[$name])) {
            throw new Exception\RuntimeException(
                "Collection with name $name is not an an array."
            );
        }

        $collection = new AssetCollection;
        $mimeType   = null;

        foreach ($this->collections[$name] as $asset) {

            if (!is_string($asset)) {
                throw new Exception\RuntimeException(
                    'Asset should be of type string. got ' . gettype($asset)
                );
            }
            if (null === ($res = $this->getAggregateResolver()->resolve($asset))) {
                throw new Exception\RuntimeException("Asset '$asset' could not be found.");
            }

            if (!$res instanceof AssetInterface) {
                throw new Exception\RuntimeException(
                    "Asset '$asset' does not implement Assetic\\Asset\\AssetInterface."
                );
            }

            if (null !== $mimeType && $res->mimetype !== $mimeType) {
                throw new Exception\RuntimeException(sprintf(
                    'Asset "%s" from collection "%s" doesn\'t have the expected mime-type "%s".',
                    $asset,
                    $name,
                    $mimeType
                ));
            }

            $mimeType = $res->mimetype;

            $this->getAssetFilterManager()->setFilters($asset, $res);

            $collection->add($res);
        }

        $collection->mimetype = $mimeType;

        return $collection;
    }

}
