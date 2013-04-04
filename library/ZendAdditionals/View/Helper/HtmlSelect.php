<?php
namespace ZendAdditionals\View\Helper;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use ZendAdditionals\Stdlib\ArrayUtils;

/**
 * Helper for select element
 */
class HtmlSelect extends \Zend\View\Helper\AbstractHtmlElement
{
    /**
     * Generates a 'Select' element.
     *
     * @param  array  $items        Array with the elements of the select
     * @param  array  $attributes   Array with the attributes for the select tag.
     * @param  string $default      The default selected element
     * @param  string $labelSuffix  Append suffix to the end of label
     * @param  string $divWrapClass Wraps a div around all created elements within this method
     *                              The value is used for the classname
     *                              Set it explicitly to null when no wrapper is wanted
     * @param  bool   $escape       Escape the items.
     *
     * @return string The select XHTML.
     */
    public function __invoke(
        array $items,
        $attributes   = false,
        $default      = null,
        $labelSuffix  = null,
        $divWrapClass = 'select',
        $escape       = true
    ) {
        $eol     = self::EOL;
        $options = '';

        if (isset($attributes['helper_message']) && empty($default) && !empty($items)) {
            $helper = array(
                '' => $attributes['helper_message'],
            );
            $items = ArrayUtils::mergeDistinct($helper, $items);
        }
        if (empty($items)) {
            // We have no items.. disable the select
            $attributes['disabled'] = true;
            if (isset($attributes['class'])) {
                $attributes['class'] .= ' disabled';
            } else {
                $attributes['class'] = 'disabled';
            }
        }

        foreach ($items as $value => $item) {
            if (!empty($labelSuffix) && $value !== '') {
                $item .= ' ' . $labelSuffix;
            }
            if ($escape) {
                $escaper = $this->view->plugin('escapeHtml');
                $item    = $escaper($item);
            }
            $optionAttributes = array(
                'value' => $value,
            );
            if ($default == $value) {
                $optionAttributes['selected'] = 'selected';
            }
            if (empty($value)) {
                $optionAttributes['class'] = 'placeholder';
            }
            $optionAttributes = $this->htmlAttribs($optionAttributes);
            $options         .= "<option{$optionAttributes}>{$item}</option>{$eol}";
        }
        $attributes = ($attributes ? $this->htmlAttribs($attributes) : '');

        $return     = "<select{$attributes}>{$eol}{$options}</select>{$eol}";
        if (!empty($divWrapClass)) {
            $return = "<div class='{$divWrapClass}'>{$eol}<span></span>{$eol}" .
                "<div class=\"arrow\"></div>{$eol}<div class=\"selectWrap\">{$eol}" .
                "{$return}</div>{$eol}</div>{$eol}";
        }
        return $return;
    }
}
