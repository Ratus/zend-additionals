<?php
namespace ZendAdditionals\View\Helper;

/**
 * @category    ZendAdditionals
 * @package     View
 * @subpackage  Helper
 */
trait HeadCombineTrait
{
    use \ZendAdditionals\Config\ConfigExtensionTrait;
    use \Zend\ServiceManager\ServiceLocatorAwareTrait;

    /**
     * @var string
     */
    protected $subDir     = 'common';

    /**
     * @var string
     */
    protected $extension  = '.txt';

    /**
     * @var string
     */
    protected $enabledKey = 'common';

    /**
     * Replace with asset collection when enabled
     *
     * @return string
     */
    public function __toString()
    {
        if ($this->getConfigItem('assets_combine.' . $this->enabledKey, false) === false) {
            return parent::__toString();
        }

        $hash    = md5(parent::__toString());
        $file    = '/' . $this->subDir . '/' . $hash . $this->extension;
        $folder  = rtrim($this->getConfigItem('assets_combine.dir', getcwd()), '/');
        $combine = true;

        // Full path to the file
        $destinationFile = $folder . $file;

        if (is_file($destinationFile) === false) {
            $combine = $this->generateCollection($destinationFile);
        }

        if ($combine) {
            $this->combineAssets($file);
        }

        return parent::__toString();
    }

    /**
     * Remove not needed assets and append the collection
     *
     * @param string $file
     * @return void
     */
    protected function combineAssets($file)
    {
        $offsets = array();

        foreach ($this->getContainer() as $offset => $item) {
            if ($this->isValidAsset($item) === false) {
                continue;
            }

            $offsets[] = $offset;
        }

        foreach ($offsets as $offset) {
            $this->getContainer()->offsetUnset($offset);
        }

        $this->appendCollection($file);
    }

    /**
     * @param  string $destinationFile
     * @return boolean
     */
    protected function generateCollection($destinationFile)
    {
        $directory = dirname($destinationFile);
        if (is_dir($directory) === false) {
            mkdir($directory, 0777, true);
        }

        $lockFile = $destinationFile . '.lock';
        if (is_file($lockFile) === true) {
            return false;
        }

        file_put_contents($lockFile, '1');

        if (is_file($destinationFile) === false) {
            file_put_contents($destinationFile, '');
        }

        $handle = fopen($destinationFile, 'r+');
        if (flock($handle, LOCK_EX) === false) {
            return false;
        }

        ftruncate($handle, 0);

        foreach ($this->getContainer() as $item) {
            if ($this->isValidAsset($item) === false) {
                continue;
            }

            $src = $this->getAssetSource($item);

            if (substr($src, 0, 7) !== 'http://') {
                $src = 'http://' . $_SERVER['HTTP_HOST'] . '/' . ltrim($src, '/');
            }

            fwrite($handle, file_get_contents($src));
        }

        fflush($handle);

        flock($handle, LOCK_UN);

        fclose($handle);

        unlink($lockFile);

        return true;
    }

    /**
     * @param mixed $item
     * @return boolean
     */
    abstract function isValidAsset($item);

    /**
     * @param string $item
     * @return string
     */
    abstract function getAssetSource($item);

    /**
     * @param string $item
     * @return void
     */
    abstract function appendCollection($item);

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->getServiceLocator()->getServiceLocator()->get('config');
    }
}
