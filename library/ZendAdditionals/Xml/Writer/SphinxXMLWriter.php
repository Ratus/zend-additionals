<?php
namespace ZendAdditionals\Xml\Writer;

use ZendAdditionals\Stdlib\ArrayUtils;

class SphinxXMLWriter extends \XMLWriter
{
    protected $fields      = array();
    protected $attributes  = array();
    protected $killStarted = false;

    /**
     * Set the schema fields for this feed, check the sphinx documentation
     * for supported attributes. Fields are used for full text indexing.
     *
     * @param array $fields like:
     * array(
     *     'some_identifier' => array(
     *         'name' => 'some_identifier',
     *         'attr' => 'string',          // optional
     *     ),
     *     'other_identifier' => array(
     *         'name' => 'other_identifier',
     *     ),
     * );
     */
    public function setFields(array $fields)
    {
        $this->fields = $fields;
    }

    /**
     * Set the schema attributes for this feed, check the sphinx documentation
     * for supported attributes.
     *
     * @param array $attributes like:
     * array(
     *     'some_identifier' => array(
     *         'name'              => 'some_identifier',
     *         'type'              => 'int',
     *         'bits'              => 11,
     *     ),
     *     'other_identifier' => array(
     *         'name'              => 'other_identifier',
     *         'type'              => 'string',
     *     ),
     * );
     */
    public function setAttributes(array $attributes)
    {
        $this->attributes = $attributes;
    }

    /**
     * Note $document must contain a field 'id' that is unique for sphinx
     *
     * @param array $document
     */
    public function addDocument(array $document)
    {
        $this->startElement('sphinx:document');
        $this->writeAttribute('id', $document['id']);

        foreach ($document as $key => $value) {
            // Check if a key/value pair should be converted to a timestamp
            if (
                'timestamp' === ArrayUtils::arrayTarget(
                    "{$key}.type", $this->attributes
                )
            ) {
                $value = strtotime($value);
            }
            // Skip the id key since that is an element attribute
            if ($key == 'id' || is_scalar($value) === false) {
                continue;
            }

            $this->startElement($key);
            $this->text($value);
            $this->endElement();

            if (
                isset($this->fields[$key]) &&
                isset($this->attributes["{$key}_crc"])
            ) {
                // We have a text field that we want to crc into the index
                $this->startElement("{$key}_crc");
                $this->text(crc32($value));
                $this->endElement();
            }
        }

        $this->endElement();
    }

    /**
     * Adds a document and prints it
     *
     * {@see self::addDocument}
     */
    public function outputDocument(array $document)
    {
        $this->addDocument($document);
        print $this->outputMemory();
    }

    /**
     * Prints string $document
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
    public function addKill(array $document)
    {
        if (!$this->killStarted) {
            $this->startElement('sphinx:killlist');
            $this->killStarted = true;
        }
        $this->startElement('id');
        $this->text($document['id']);
        $this->endElement();
    }

    /**
     * Print killist entry for sphinx xml
     *
     * {@see self::addKill}
     */
    public function outputKill(array $document)
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
            foreach ($field as $key => $value) {
                $this->writeAttribute($key, $value);
            }
            $this->endElement();
        }

        // add attributes to the schema
        foreach ($this->attributes as $attribute) {
            $this->startElement('sphinx:attr');
            foreach ($attribute as $key => $value) {
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
