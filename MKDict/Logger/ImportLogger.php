<?php


namespace MKDict\Logger;

use MKDict\Logger\Logger;
use MKDict\Database\JMDictEntity;
use MKDict\FileResource\LogFileResource;

class ImportLogger extends Logger
{
    protected $logfile;
    protected $new_entries;
    protected $unimported_entries; //entries that failed to be imported
    protected $expired_entries;
    protected $new_kanjis;
    protected $expired_kanjis;
    protected $new_readings;
    protected $expired_readings;
    protected $new_senses;
    protected $expired_senses;
    protected $libxml_warnings;
    protected $warnings;
    
    public function __construct()
    {
        $this->new_entries = array();
        $this->unimported_entries = array();
        $this->expired_entries = array();
        $this->new_kanjis = array();
        $this->expired_kanjis = array();
        $this->new_readings = array();
        $this->expired_readings = array();
        $this->new_senses = array();
        $this->expired_senses = array();
        $this->libxml_warnings = array();
        $this->warnings = array();
        $this->logfile = new LogFileResource();
    }
    
    public function invalid_int($data, JMDictEntity $entry, string $msg = "Invaild integer data")
    {
        $this->unimported_entries[] = array($msg, $entry, $data);
    }
    
    public function invalid_string($data, JMDictEntity $entry, string $msg = "Invalid string data")
    {
        $this->unimported_entries[] = array($msg, $entry, $data);
    }
    
    public function libxml_warning(\libXMLError $warning)
    {
        $this->libxml_warnings[] = $warning;
    }
    
    public function duplicate_sequence_id(JMDictEntity $entry)
    {
        $this->unimported_entries[] = array("Duplicate sequence id", $entry);
    }
    
    public function sequence_id_missing_or_invalid(JMDictEntity $entry)
    {
        $this->unimported_entries[] = array("Missing or invalid sequence id", $entry);
    }
    
    public function missing_reading_element(JMDictEntity $entry)
    {
        $this->unimported_entries[] = array("Missing reading element", $entry);
    }
    
    public function missing_sense_element(JMDictEntity $entry)
    {
        $this->unimported_entries[] = array("Missing sense element", $entry);
    }
    
    public function missing_binary_field(JMDictEntity $entry)
    {
        $this->unimported_entries[] = array("Missing binary field on one or more elements", $entry);
    }
    
    public function restr_target_not_found(JMDictEntity $entry, JMDictEntity $reading)
    {
        $this->warnings[] = array("Restr target not found", $entry, $reading);
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
    
    public function stagr_target_not_found(JMDictEntity $entry, JMDictEntity $sense)
    {
        $this->warnings[] = array("Stagr target not found", $entry, $sense);
    }
    
    public function stakr_target_not_found(JMDictEntity $entry, JMDictEntity $sense)
    {
        $this->warnings[] = array("Stagk target not found", $entry, $sense);
    }
    
    public function invalid_reference_type(array $reference_type, $msg = "Invalid reference type")
    {
         $this->warnings[] = array($msg, $reference_type);
    }
    
    public function k_and_r_search_failure($kanji_binary, $reading_binary, $sense_index)
    {
        $this->warnings[] = array("Failed to find reference type: ", null, compact("kanji_binary", "reading_binary", "sense_index"));
    }
    
    public function k_or_r_search_failure($stringv, $sense_index)
    {
        $this->warnings[] = array("Failed to find reference type: ", null, compact("stringv","sense_index"));
    }
    
    public function flush()
    {
        $this->finish_time = time() - $this->start_time;
        $this->time_taken = $this->finish_time - $this->start_time;
        $this->net_time = sprintf("Net Time: %dh:%dm:%ds\n", $this->time_taken / (60*60), ($this->time_taken % (60*60)) / 60, ($this->time_taken % (60*60)) % 60);
        
        
    }
}
