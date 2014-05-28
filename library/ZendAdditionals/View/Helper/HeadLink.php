<?php
namespace ZendAdditionals\View\Helper;

use \Zend\ServiceManager\ServiceLocatorAwareInterface;

/**
 * @category    ZendAdditionals
 * @category    View
 * @subpackage  Helper
 */
class HeadLink extends \Zend\View\Helper\HeadLink implements ServiceLocatorAwareInterface
{
    use \ZendAdditionals\View\Helper\HeadCombineTrait;

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
       parent::__construct();

       $this->enabledKey = 'head_link';
       $this->subDir     = 'css';
       $this->extension  = '.css';
    }

    /**
     * {@inheritdoc}
     */
    protected function appendCollection($src)
    {
        $this->appendStylesheet($src);
    }

    /**
    * {@inheritdoc}
    */
    protected function isValidAsset($item)
    {
       return ($item->rel === 'stylesheet');
    }

    /**
    * {@inheritdoc}
    */
    protected function getAssetSource($item)
    {
       return $item->href;
    }
}
