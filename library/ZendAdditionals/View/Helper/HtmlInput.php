<?php
namespace ZendAdditionals\View\Helper;

use Zend\ServiceManager\ServiceLocatorAwareInterface;

/**
 * Helper for textarea element
 */
class HtmlInput extends \Zend\View\Helper\AbstractHtmlElement
{

    /**
     * Generates a 'TextInput' element.
     *
     * @param  array  $attributes   Attributes for the input tag.
     * @param  string $divWrapClass Wraps a div around all created elements within this method
     *                              The value is used for the classname
     *                              Set it explicitly to null when no wrapper is wanted
     * @param  bool   $escape       Escape the contents.
     *
     * @return string The input  XHTML.
     */
    public function __invoke(
        $attributes   = false,
        $divWrapClass = 'input',
        $escape       = true
    ) {
        $eol        = self::EOL;

        $attributes = ($attributes ? $this->htmlAttribs($attributes) : '');
        $return     = "<span></span>{$eol}<input{$attributes} />{$eol}";

        if (!empty($divWrapClass)) {
            $return = "<div class='{$divWrapClass}'>{$eol}{$return}</div>{$eol}";
        }

        return $return;
    }
}
