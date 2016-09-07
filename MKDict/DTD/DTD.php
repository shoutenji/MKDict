<?php


namespace MKDict\DTD;

use MKDict\FileResource\FileResource;
use MKDict\DTD\Canonicalizable;
use MKDict\DTD\DTDParser;

/**
 * Class representing a DTD
 * 
 * @author Taylor B <taylorbrontario@riseup.net>
 */
class DTD implements Canonicalizable
{
    public $raw;
    public $canonical;
    public $version;
    public $dictionary_version;
    public $version_id;
    
    public $document_name;
    public $elements;
    public $attributes;
    public $entities;
    
    protected $file;
    protected $dtd_parser;
    
    /**
     * Constructor
     * 
     * @param FileResource $file The dictionary file
     */
    public function __construct(FileResource $file = null)
    {
        if(!empty($file))
        {
            $this->dtd_parser = new DTDParser($file);
        }
    }
    
    /**
     * Check for two canonicalizable objects for equality
     * 
     * @param \MKDict\DTD\Canonicalizable $dtd
     * 
     * @return bool True if canonicalized forms are eqaul, false otherwise
     */
    public function is_equal(Canonicalizable $dtd)
    {
        return $this->canonical === $dtd->canonical;
    }
    
    /**
     * @return void
     */
    public function canonicalize()
    {
        $this->canonical = $this->dtd_parser->canonicalize();
        $this->raw = $this->dtd_parser->get_raw();
        $this->document_name = $this->dtd_parser->get_document_name();
        $this->elements = $this->dtd_parser->get_elements();
        $this->atrributes = $this->dtd_parser->get_attributes();
        $this->entities = $this->dtd_parser->get_entities();
    }
    
    /**
     * Serialize returns canonical form as a string
     * 
     * @return string
     */
    public function serialize()
    {
        return $this->canonical;
    }
    
    /**
     * Unserialize
     * 
     * @param type $canonicalizable
     */
    public function unserialize($canonicalizable)
    {
        
    }
}
