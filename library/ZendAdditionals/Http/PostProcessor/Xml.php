<?php

namespace ZendAdditionals\Http\PostProcessor;

/**
 * Just an example. Do not use.
 */
class Xml extends AbstractPostProcessor
{
    public function process()
    {
        $result = $this->toXML($this->getVariables());

        $this->getResponse()->setContent($result);

        $headers = $this->getResponse()->getHeaders();
        $headers->addHeaderLine('Content-Type', 'text/xml');
        $this->getResponse()->setHeaders($headers);
    }

    public function toXml($data, $rootNodeName = 'data', $xml=null)
    {
        // turn off compatibility mode as simple xml throws a wobbly if you don't.
        if (ini_get('zend.ze1_compatibility_mode') == 1) {
            ini_set ('zend.ze1_compatibility_mode', 0);
        }

        if ($xml == null) {
            $xml = simplexml_load_string("<?xml version='1.0' encoding='utf-8'?><$rootNodeName />");
        }

        // loop through the data passed in.
        foreach($data as $key => $value)
        {
            // no numeric keys in our xml please!
            if (is_numeric($key)) {
                // make string key...
                $key = sprintf("element_%d", $key);
            }

            // replace anything not alpha numeric
            $key = preg_replace('/[^a-zA-Z0-9_]/i', '', $key);

            // if there is another array found recrusively call this function
            if (is_array($value) || is_object($value)) {
                $node = $xml->addChild($key);
                // recrusive call.
                $this->toXml($value, $rootNodeName, $node);
            } else {
                // add single node.
                $value = htmlentities($value);
                $xml->addChild($key,$value);
            }

        }
        // pass back as string. or simple xml object if you want!
        return $xml->asXML();
    }
}

