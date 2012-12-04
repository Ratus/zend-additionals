<?php
namespace ZendAdditionals\Xml\Feed;

class SphinxXMLFeed extends \XMLWriter 
{
    private $fields = array();
    private $attributes = array();

    public function __construct($options = array()) 
    {
        $defaults = array(
            'indent' => false,
        );
        $options = array_merge($defaults, $options);

        // Store the xml tree in memory
        $this->openMemory();

        if ($options['indent']) {
            $this->setIndent(true);
        }
    }

    public function setFields($fields) 
    {
        $this->fields = $fields;
    }

    public function setAttributes($attributes) 
    {
        $this->attributes = $attributes;
    }

    /**
     * Note $document must contain a field 'id' that is unique for sphinx
     * 
     * @param array $document
     */
    public function addDocument($document) 
    {
        $this->startElement('sphinx:document');
        $this->writeAttribute('id', $document['id']);

        foreach ($document as $key => $value) {
            // Skip the id key since that is an element attribute
            if ($key == 'id')
                continue;

            $this->startElement($key);
            $this->text($value);
            $this->endElement();
        }

        $this->endElement();
        print $this->outputMemory();
    }

    public function beginOutput() 
    {
        $this->startDocument('1.0', 'UTF-8');
        $this->startElement('sphinx:docset');
        $this->startElement('sphinx:schema');

        // add fields to the schema
        foreach ($this->fields as $field) {
            $this->startElement('sphinx:field');
            $this->writeAttribute('name', $field);
            $this->endElement();
        }

        // add attributes to the schema
        foreach ($this->attributes as $attributes) {
            $this->startElement('sphinx:attr');
            foreach ($attributes as $key => $value) {
                $this->writeAttribute($key, $value);
            }
            $this->endElement();
        }

        // end sphinx:schema
        $this->endElement();
        print $this->outputMemory();
    }

    public function endOutput() 
    {
        // end sphinx:docset
        $this->endElement();
        print $this->outputMemory();
    }
}
