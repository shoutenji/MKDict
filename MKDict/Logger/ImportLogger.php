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
    
    public function duplicate_sequence_id(JMDictEntity $entry)
    {
        
    }
    
    public function sequence_id_missing_or_invalid(JMDictEntity $entry)
    {
        
    }
    
    public function missing_reading_element(JMDictEntity $entry)
    {
        
    }
    
    public function missing_sense_element(JMDictEntity $entry)
    {
        
    }
    
    public function missing_binary_field(JMDictEntity $entry)
    {
        
    }
    
    public function restr_target_not_found(JMDictEntity $entry, JMDictEntity $reading)
    {
        
    }
    
    public function new_entry(JMDictEntity $new_entry, int $version_id)
    {
        
    }
    
    public function expired_entry(JMDictEntity $expired_entry, int $version_id)
    {
        
    }
    
    public function new_kanji(JMDictEntity $expired_kanji, int $version_id)
    {
        
    }
    
    public function expired_kanji(JMDictEntity $expired_kanji, int $version_id)
    {
        
    }
    
    public function new_reading(JMDictEntity $new_reading, int $version_id)
    {
        
    }
    
    public function expired_reading(JMDictEntity $expired_reading, int $version_id)
    {
        
    }
    
    public function new_sense(JMDictEntity $new_sense, int $version_id)
    {
        
    }
    
    public function expired_sense(JMDictEntity $expired_sense, int $version_id)
    {
        
    }
    
    public function stagr_target_not_found(JMDictEntity $this_entry, JMDictEntity $this_sense)
    {
        
    }
    
    public function stakr_target_not_found(JMDictEntity $this_entry, JMDictEntity $this_sense)
    {
        
    }
    
    public function invalid_reference_type($msg = "", array $reference_type)
    {
        
    }
    
    public function k_and_r_search_failure(array $args)
    {
        
    }
    
    public function k_or_r_search_failure(array $args)
    {
        
    }
    
}
