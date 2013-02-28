<?php
namespace ZendAdditionals\View\Helper;

/**
 * Helper for select element
 */
class HtmlSelect extends \Zend\View\Helper\AbstractHtmlElement
{
    /**
     * Generates a 'Select' element.
     *
     * @param  array  $items      Array with the elements of the select
     * @param  array  $attributes Attributes for the select tag.
     * @param  string $default    The default selected element
     * @param  bool   $escape     Escape the items.
     * @return string The select XHTML.
     */
    public function __invoke(
        array $items,
        $attributes = false,
        $default    = null,
        $escape     = true
    ) {
        $eol     = self::EOL;
        $options = '';
        foreach ($items as $value => $item) {
            if ($escape) {
                $escaper = $this->view->plugin('escapeHtml');
                $item    = $escaper($item);
            }
            $selected = ($default === $value) ? " selected='selected'" : '';
            $options .= "<option value='{$value}'{$selected}>{$item}</option>{$eol}";
        }
        $attributes = ($attributes ? $this->htmlAttribs($attributes) : '');

        return "<select{$attributes}>{$eol}{$options}</select>{$eol}";
    }
}
