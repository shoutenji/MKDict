<?php

namespace MKDict\DTD;

use MKDict\FileResource\FileResource;
use MKDict\DTD\Exception\DTDMissingException;
use MKDict\DTD\Exception\DTDError;

/**
 * Interface Canonicalizable. Objects implementing this have a canonical form useful for comparison
 * 
 * @author Taylor B <taylorbrontario@riseup.net>
 */
interface Canonicalizable extends \Serializable
{
    /**
     * Check for two canonicalizable objects for equality
     * 
     * @param \MKDict\DTD\Canonicalizable $dtd
     */
    public function is_equal(self $dtd);
    
    /**
     * Transform data into canonical form
     */
    public function canonicalize();
    
    /**
     * Serialize
     */
    public function serialize();
    
    /**
     * Unserialize
     * 
     * @param type $object
     */
    public function unserialize(self $object);
}
