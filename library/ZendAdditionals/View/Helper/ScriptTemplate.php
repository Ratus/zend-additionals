<?php
namespace ZendAdditionals\View\Helper;

/**
 * Helper for script-template element
 */
class ScriptTemplate extends \Zend\View\Helper\AbstractHtmlElement
{
    /**
     * Generate HTML template inside script tags
     *
     * @param  string $id        The ID of the template
     * @param  string $template  The template himself
     * @param  string $type      The type of the script
     * @param  array  $attributes Extra attributes to set on the script tag
     * @return string
     */
    public function __invoke(
        $id,
        $template,
        $type = 'x-js-template',
        array $attributes = array()
    ) {
        $eol        = self::EOL;

        $attributes = array_merge($attributes, array('id' => $id, 'type' => $type));
        $attributes = $this->htmlAttribs($attributes);

        return "<script{$attributes}>{$eol}{$template}{$eol}</script>";
    }
}
