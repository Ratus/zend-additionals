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
     * @param  array  $items            Array with the elements of the select
     * @param  array  $attributes       Array with the attributes for the select tag.
     * @param  string $default          The default selected element
     * @param  string $labelSuffix      Append suffix to the end of label
     * @param  string $divWrapClass     Wraps a div around all created elements within this method
     *                                  The value is used for the classname
     *                                  Set it explicitly to null when no wrapper is wanted
     * @param  bool   $escape           Escape the items.
     * @param  array  $optionAttributes Attributes for the options element. Use $items key as key
     *
     * @return string The select XHTML.
     */
    public function __invoke(
        array $items,
        $attributes       = false,
        $default          = null,
        $labelSuffix      = null,
        $divWrapClass     = 'select',
        $escape           = true,
        $optionAttributes = array()
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

            // Default param
            $tmpOptionAttributes = array(
                'value' => $value,
            );

            // Set selected
            if ($default == $value) {
                $tmpOptionAttributes['selected'] = 'selected';
            }

            // key => '' or key => 0 set class placeholder
            if (empty($value)) {
                $tmpOptionAttributes['class'] = 'placeholder';
            }

            // Merge options given from the argument line
            if (array_key_exists($value, $optionAttributes)) {
                $tmpOptionAttributes = ArrayUtils::mergeDistinct(
                    $optionAttributes[$value],
                    $tmpOptionAttributes
                );
            }

            $tmpOptionAttributes = $this->htmlAttribs($tmpOptionAttributes);
            $options         .= "<option{$tmpOptionAttributes}>{$item}</option>{$eol}";
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
