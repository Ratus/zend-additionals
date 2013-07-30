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
        $result = parent::resolve($name);
        if (!($result instanceof \Assetic\Asset\AssetCollection)) {
            return null;
        }
        $collection = new AssetCollection;
        
        $collection->mimetype = $result->mimetype;
        $collection->setAll($result->all());

        return $collection;
    }

}
