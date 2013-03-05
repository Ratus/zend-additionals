<?php
namespace ZendAdditionals\View\Helper;

use Zend\ServiceManager\ServiceLocatorAwareInterface;

/**
 * Helper for select element
 */
class HtmlSelect extends \Zend\View\Helper\AbstractHtmlElement implements
    ServiceLocatorAwareInterface
{
    use \Zend\ServiceManager\ServiceLocatorAwareTrait;

    /**
     * Generates a 'Select' element.
     *
     * @param  array  $items       Array with the elements of the select
     * @param  array  $attributes  Attributes for the select tag.
     * @param  string $default     The default selected element
     * @param  string $labelSuffix Append suffix to the end of label
     * @param  bool   $escape      Escape the items.
     * @return string The select XHTML.
     */
    public function __invoke(
        array $items,
        $attributes  = false,
        $default     = null,
        $labelSuffix = null,
        $escape      = true
    ) {
        $eol     = self::EOL;
        $options = '';
        foreach ($items as $value => $item) {
            if (!empty($labelSuffix)) {
                $item .= ' ' . $labelSuffix;
            }
            if ($escape) {
                $escaper = $this->view->plugin('escapeHtml');
                $item    = $escaper($item);
            }
            $selected = ($default == $value) ? " selected='selected'" : '';
            $class = '';
            if (empty($value)) {
                $class = ' class="placeholder"';
            }
            $options .= "<option{$class} value='{$value}'{$selected}>{$item}</option>{$eol}";
        }
        $attributes = ($attributes ? $this->htmlAttribs($attributes) : '');

        return "<select{$attributes}>{$eol}{$options}</select>{$eol}";
    }
}
