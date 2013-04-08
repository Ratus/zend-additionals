<?php
namespace ZendAdditionals\Assetic\Filter;

use Assetic\Asset\AssetInterface;
use Assetic\Filter\FilterInterface;
use ZendAdditionals\AssetManager\Service\AssetFilterManager;

class UniversalLessFilter implements FilterInterface
{
    /**
     * @var FilterInterface
     */
    protected $filter;

    /**
     * @var array
     */
    protected $lessVars = array();

    /**
     * @var string
     */
    protected $parsedVars;

    public function __construct(FilterInterface $filter, $lessVars = array())
    {
        $this->filter             = $filter;
        $this->lessVars           = $lessVars;
    }

    public function filterLoad(AssetInterface $asset)
    {
        $asset->setContent($this->getVars() . $asset->getContent());
        $this->filter->filterLoad($asset);
    }

    /**
     * {@inheritDoc}
     */
    public function filterDump(AssetInterface $asset)
    {
        $this->filter->filterDump($asset);
    }

    protected function getVars()
    {
        if ($this->parsedVars !== null) {
            return $this->parsedVars;
        }

        $this->parsedVars = '';
        foreach ($this->lessVars as $key => $value) {
            $this->parsedVars .= "@{$key}: {$value};\n";
        }

        return $this->parsedVars;
    }
}