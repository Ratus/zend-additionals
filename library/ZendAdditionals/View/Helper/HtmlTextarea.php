<?php
namespace ZendAdditionals\View\Helper;

use Zend\ServiceManager\ServiceLocatorAwareInterface;

/**
 * Helper for textarea element
 */
class HtmlTextarea extends \Zend\View\Helper\AbstractHtmlElement implements
    ServiceLocatorAwareInterface
{
    use \Zend\ServiceManager\ServiceLocatorAwareTrait;

    /**
     * Generates a 'Textarea' element.
     *
     * @param  string $contents    string with the contents of the textarea
     * @param  array  $attributes  Attributes for the textarea tag.
     * @param  bool   $escape      Escape the contents.
     * @return string The textarea XHTML.
     */
    public function __invoke(
        $content      = '',
        $attributes   = false,
        $divWrapClass = 'textarea',
        $escape       = true
    ) {
        $eol        = self::EOL;

        if ($escape) {
            $escaper = $this->view->plugin('escapeHtml');
            $content = $escaper($content);
        }
        $attributes = ($attributes ? $this->htmlAttribs($attributes) : '');
        $return     = "<span></span>{$eol}<textarea{$attributes}>{$content}</textarea>{$eol}";
        if (!empty($divWrapClass)) {
            $return = "<div class='{$divWrapClass}'>{$eol}{$return}</div>{$eol}";
        }
        return $return;
    }
}
