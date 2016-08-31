<?php


namespace MKDict\Logger;

use MKDict\Logger\Logger;
use MKDict\Database\JMDictEntity;

class ImportLogger extends Logger
{
    public function flush()
    {
        
    }
    
    public function invalid_int($data, JMDictEntity $entry, string $msg = "")
    {
        
    }
    
    public function invalid_string($data, JMDictEntity $entry, string $msg = "")
    {
        
    }
    
    public function libxml_warning(\libXMLError $warning)
    {
        
    }
}
