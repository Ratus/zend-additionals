<?php
namespace ZendAdditionals\Xml;

class Object
{
    protected $parsed     = false;
    protected $xml        = null;
    protected $xmlValues  = null;
    protected $xmlIndexes = null;
    
    public function __construct($xml)
    {
        $this->setXml($xml);
    }
    
    public function setXml($xml)
    {
        $this->xml = $xml;
        return $this;
    }
    
    protected function readXml()
    {
        if ($this->parsed) {
            return;
        }
        $parser = xml_parser_create('UTF-8');
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, true);
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, false);
        xml_parse_into_struct($parser, trim($this->xml), $this->xmlValues, $this->xmlIndexes);
        xml_parser_free($parser);
        $this->parsed = true;
    }
    
    protected function writeXml()
    {
        $data = '<?xml version="1.0" encoding="iso-8859-1"?>' . "\n";

        foreach ($this->xmlValues as $struct) {
            $data .= str_repeat('    ', $struct['level']-1);
            $attributes = '';
            if (isset($struct['attributes'])) {
                foreach ($struct['attributes'] as $key => $value) {
                    $attributes .= ' ' . $key . '="' . $value. '"';
                }
            }
            switch($struct['type']) {
                case 'open':
                    $data .= "<{$struct['tag']}{$attributes}>\n";
                    break;
                case 'close':
                    $data .= "</{$struct['tag']}>\n";
                    break;
                case 'complete':
                    if (preg_match('!("|\'|&|\<|\>|\\\\)!', $struct['value'])) {
                        $struct['value'] = "<![CDATA[{$struct['value']}]]>";
                    }
                    $data .= "<{$struct['tag']}{$attributes}>{$struct['value']}</{$struct['tag']}>\n";
                    break;
            }
        }
        $this->xml = $data;
    }
    
    protected function writeValue($key, $value)
    {
        $this->readXml();
        $structValue = array(
            'tag'   => $key,
            'type'  => 'complete',
            'level' => 2,
            'value' => $value,
        );
        if (isset($this->xmlIndexes[$key][0])) {
            $this->xmlValues[$this->xmlIndexes[$key][0]] = $structValue;
        } else {
            $maxIndex = max(array_keys($this->xmlValues));
            array_splice($this->xmlValues, $maxIndex, 0, array($structValue));
            $this->xmlIndexes[$key][0] = $maxIndex;
        }
    }
    
    protected function readValue($key)
    {
        $this->readXml();
        if (isset($this->xmlIndexes[$key][0])) {
            if ($this->xmlValues[$this->xmlIndexes[$key][0]]['level'] !== 2) {
                throw new \Exception('Xml Object does not support nested values yet!');
            }
            return $this->xmlValues[$this->xmlIndexes[$key][0]]['value'];
        }
        return null;
    }
    
    public function getXml()
    {
        $this->writeXml();
        return $this->xml;
    }
    
    public function getValue($key)
    {
        return $this->readValue($key);
    }
    
    public function getValues(array $keys)
    {
        $return = array();
        foreach ($keys as $key) {
            $return[$key] = $this->readValue($key);
        }
        return $return;
    }
    
    public function setValue($key, $value)
    {
        $this->writeValue($key, $value);
        return $this;
    }
}
