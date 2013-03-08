<?php
namespace ZendAdditionals\View\Helper;

use Zend\ServiceManager\ServiceLocatorAwareInterface;

/**
 * Helper for select element
 */
class HtmlTextarea extends \Zend\View\Helper\AbstractHtmlElement implements
    ServiceLocatorAwareInterface
{
    use \Zend\ServiceManager\ServiceLocatorAwareTrait;

    /**
     * Generates a 'Select' element.
     *
     * @param  array  $contents       Array with the elements of the select
     * @param  array  $attributes  Attributes for the select tag.
     * @param  string $default     The default selected element
     * @param  bool   $escape      Escape the contents.
     * @return string The select XHTML.
     */
    public function __invoke(
        $content      = '',
        $attributes   = false,
        $default      = null,
        $divWrapClass = 'textarea',
        $escape       = true
    ) {
        $eol     = self::EOL;
        $options = '';

        if ($escape) {
            $escaper = $this->view->plugin('escapeHtml');
            $content    = $escaper($content);
        }
        $attributes = ($attributes ? $this->htmlAttribs($attributes) : '');

        $return = "<textarea{$attributes}>{$eol}{$options}</textarea>{$eol}";
        if (!empty($divWrapClass)) {
            return "<div class='{$divWrapClass}'><span></span>{$return}</div>";
        }
    }
}
