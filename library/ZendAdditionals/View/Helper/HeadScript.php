<?php
namespace ZendAdditionals\View\Helper;

use \Zend\ServiceManager\ServiceLocatorAwareInterface;

/**
 * @category    ZendAdditionals
 * @category    View
 * @subpackage  Helper
 */
class HeadScript extends \Zend\View\Helper\HeadScript implements ServiceLocatorAwareInterface
{
   use \ZendAdditionals\View\Helper\HeadCombineTrait;

   /**
    * {@inheritdoc}
    */
   public function __construct()
   {
       parent::__construct();

       $this->enabledKey = 'head_script';
       $this->subDir     = 'js';
       $this->extension  = '.js';
   }

   /**
     * {@inheritdoc}
     */
    protected function appendCollection($src)
    {
        $this->appendFile($src);
    }

   /**
    * {@inheritdoc}
    */
   protected function isValidAsset($item)
   {
       return array_key_exists('src', $item->attributes);
   }

   /**
    * {@inheritdoc}
    */
   protected function getAssetSource($item)
   {
       return $item->attributes['src'];
   }
}
