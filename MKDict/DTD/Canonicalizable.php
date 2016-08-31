<?php

namespace MKDict\DTD;

use MKDict\FileResource\FileResource;
use MKDict\DTD\Exception\DTDMissingException;
use MKDict\DTD\Exception\DTDError;

interface Canonicalizable extends \Serializable
{
    public function is_equal(Canonicalizable $dtd);
    
    public function canonicalize();
    
    public function serialize();
    
    public function unserialize($object);
}
