<?php
namespace ZendAdditionals\View\Helper\Attribute;

use ZendAdditionals\View\Helper;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\View\Helper\AbstractHelper;
use Zend\View\Exception;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Helper for select element
 */
class HtmlSelect extends AbstractHelper implements ServiceLocatorAwareInterface
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
     * Generates a 'Select' element based on an entity attribute.
     *
     * @param  string $label             Label of the enumeration attribute
     * @param  string $entityIdentifier
     * @param  array  $attributes        Attributes for the select tag.
     * @param  string $default           The default selected element
     * @param  string $translationPrefix When set try to translate label with prefix
     * @param  string $labelSuffix       Append suffix to the end of label
     * @param  string $divWrapClass      Wraps a div around all created elements
     *                                   The value is used for the classname
     *                                   Set explicitly to null when no wrapper is wanted
     * @param  bool   $escape            Escape the items.
     *
     * @return string The select XHTML.
     */
    public function __invoke(
        $label,
        $entityIdentifier,
        $attributes        = false,
        $default           = null,
        $translationPrefix = null,
        $labelSuffix       = null,
        $divWrapClass      = 'select',
        $escape            = true
    ) {
        $mapperServiceName = $this->getConfigItem(
            'view_helpers.htmlselect.attribute.' .
            "entity_identifiers.{$entityIdentifier}"
        );

        if (empty($mapperServiceName)) {
            throw new Exception\InvalidArgumentException(
                'Can not generate html select for given ' .
                'entity identifier: ' . $entityIdentifier
            );
        }

        $mapper = $this->getServiceLocator()->get($mapperServiceName);

        if (!($mapper instanceof \ZendAdditionals\Db\Mapper\AbstractMapper)) {
            throw new Exception\InvalidArgumentException(
                'The given mapper service name is ' .
                'incorrect for entity: ' . $entityIdentifier
            );
        }

        $enumAttributes = $mapper->getEnumAttributes($label);

        $select         = array();$translator = $this->pluginManager->get('translate');
        $translate      = false;
        if (!empty($translationPrefix)) {
            $translate = true;
        }
        foreach ($enumAttributes as $selectValue) {
            $select[$selectValue] = (
                $translate ?
                $translator($translationPrefix . $selectValue) :
                $selectValue
            );
        }

        if (!is_array($attributes) || !isset($attributes['name'])) {
            $attributes['name'] = $label;
        }

        $htmlSelect = $this->pluginManager->get('htmlselect');
        $htmlSelect->setView($this->getView());
        return $htmlSelect(
            $select,
            $attributes,
            $default,
            $labelSuffix,
            $divWrapClass,
            $escape
        );
    }
}
