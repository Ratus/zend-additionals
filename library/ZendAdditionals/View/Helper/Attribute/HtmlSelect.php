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

    /**
     * Get service locator
     *
     * @return ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        $locator = $this->serviceLocator;
        if ($locator instanceof \Zend\ServiceManager\AbstractPluginManager) {
            return $locator->getServiceLocator();
        }
        return $locator;
    }
    
    /**
     * Generates a 'Select' element based on an entity attribute.
     *
     * @param  string $label            Label of the enumeration attribute
     * @param  string $entityIdentifier 
     * @param  array  $attributes       Attributes for the select tag.
     * @param  string $default          The default selected element
     * @param  bool   $escape           Escape the items.
     * @return string The select XHTML.
     */
    public function __invoke(
        $label, 
        $entityIdentifier, 
        $attributes = false,
        $default    = null,
        $escape     = true
    ) {
        $mapperServiceName = $this->getConfigItem(
            "view_helpers.htmlselect.attribute." .
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

        $select = array();
        foreach ($enumAttributes as $selectValue) {
            $select[$selectValue] = $selectValue;
        }
        
        if (!is_array($attributes) || !isset($attributes['name'])) {
            $attributes['name'] = $entityIdentifier . '_' . $label;
        }
        
        $htmlSelect = new Helper\HtmlSelect;
        $htmlSelect->setView($this->getView());
        return $htmlSelect(
            $select,
            $attributes,
            $default,
            $escape
        );
    }
}