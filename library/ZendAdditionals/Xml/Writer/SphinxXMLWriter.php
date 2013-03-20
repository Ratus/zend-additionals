<?php
namespace ZendAdditionals\Xml\Writer;

class SphinxXMLWriter extends \XMLWriter
{
    private $fields      = array();
    private $attributes  = array();
    private $killStarted = false;

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
    }

    /**
     * Adds a document and prints it
     *
     * {@see self::addDocument}
     */
    public function outputDocument($document)
    {
        $this->addDocument($document);
        print $this->outputMemory();
    }

    /**
     * Prints $document
     */
    public function outputRawDocument($document)
    {
        print preg_replace('/^/m', '    ', $document);
    }

    /**
     * Note $document must contain a field 'id' that is unique for sphinx
     *
     * @param array $document
     */
    public function addKill($document)
    {
        if (!$this->killStarted) {
            $this->startElement('sphinx:killlist');
            $this->killStarted = true;
        }
        $this->startElement('id');
        $this->text($document['id']);
        $this->endElement();
    }

    public function outputKill($document)
    {
        $this->addKill($document);
        print $this->outputMemory();
    }

    /**
     * Create the beginning of the sphinx xml document
     *
     * Use the methods setFields and setAttributes for proper
     * field and attribute info in the beginning of the document
     */
    public function beginDocument()
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
    }

    /**
     * Print the beginning of the sphinx xml document
     */
    public function beginOutput()
    {
        $this->beginDocument();
        print $this->outputMemory();
    }

    /**
     * Print the end of the sphinx xml document
     */
    public function endOutput()
    {
        // end sphinx:docset
        $this->endElement();
        if ($this->killStarted) {
            $this->killStarted = false;
            $this->endElement();
        }
        print $this->outputMemory();
    }
}
