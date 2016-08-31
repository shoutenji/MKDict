<?php


namespace MKDict\DTD;

use MKDict\FileResource\FileResource;
use MKDict\DTD\Canonicalizable;
use MKDict\DTD\DTDParser;

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
    
    public function __construct(FileResource $file = null)
    {
        if(!empty($file))
        {
            $this->dtd_parser = new DTDParser($file);
        }
    }
    
    public function is_equal(Canonicalizable $dtd)
    {
        return $this->canonical === $dtd->canonical;
    }
    
    public function canonicalize()
    {
        $this->canonical = $this->dtd_parser->canonicalize();
        $this->raw = $this->dtd_parser->get_raw();
        $this->document_name = $this->dtd_parser->get_document_name();
        $this->elements = $this->dtd_parser->get_elements();
        $this->atrributes = $this->dtd_parser->get_attributes();
        $this->entities = $this->dtd_parser->get_entities();
    }
    
    public function serialize()
    {
        return $this->canonical;
    }
    
    public function unserialize($obj)
    {
        
    }
}
