<?php


namespace MKDict\Logger;

use MKDict\Logger\Logger;
use MKDict\Database\JMDictEntity;
use MKDict\FileResource\LogFileResource;

/**
 * A logging class
 * 
 * @author Taylor B <taylorbrontario@riseup.net>
 * 
 * @todo make different log formats for first time imports and all successive imports
 */
class ImportLogger extends Logger
{
    /**
     * Constructor
     * 
     * @return void
     */
    public function __construct()
    {
        parent::__construct("import");
    }
    
    /**
     * Log an invalid int error
     * 
     * @param mixed $data The invalid data
     * @param JMDictEntity $entry The entry object the invalid data occurred in
     * @param string $msg Error message
     * 
     * @return void
     */
    public function invalid_int($data, JMDictEntity $entry, string $msg = "Invaild integer data")
    {
        $this->unimported_entries[] = array($msg, $entry, $data);
    }
    
    /**
     * Log an invalid string error
     * 
     * @param mixed $data The invalid data
     * @param JMDictEntity $entry The entry object the invalid data occurred in
     * @param string $msg Error message
     * 
     * @return void
     */
    public function invalid_string($data, JMDictEntity $entry, string $msg = "Invalid string data")
    {
        $this->unimported_entries[] = array($msg, $entry, $data);
    }
    
    /**
     * Log a libxml warning
     * 
     * @param libXMLError $warning The libxml error
     * 
     * @return void
     */
    public function libxml_warning(\libXMLError $warning)
    {
        $this->libxml_warnings[] = $warning;
    }
    
    /**
     * Log an duplicate sequence id error
     * 
     * @param JMDictEntity $entry The entry
     * @param int $sequence_id The sequence id
     * 
     * @return void
     */
    public function duplicate_sequence_id(JMDictEntity $entry = null, int $sequence_id = 0)
    {
        if(empty($entry))
        {
            $this->unimported_entries[] = array("Duplicate sequence id $sequence_id. The entry corresponding to this sequence_id has been over-written one or more times.", null, $sequence_id);
        }
        else
        {
            $this->unimported_entries[] = array("Duplicate sequence id. Entry not imported.", $entry);
        }
    }
    
    /**
     * Log an duplicate or missing sequence id error
     * 
     * @param JMDictEntity $entry The entry
     * 
     * @return void
     */
    public function sequence_id_missing_or_invalid(JMDictEntity $entry)
    {
        $this->unimported_entries[] = array("Missing or invalid sequence id", $entry);
    }
    
    /**
     * Log an missing reading element error
     * 
     * @param JMDictEntity $entry The entry
     * 
     * @return void
     */
    public function missing_reading_element(JMDictEntity $entry)
    {
        $this->unimported_entries[] = array("Missing reading element", $entry);
    }
    
    /**
     * Log an missing sense element error
     * 
     * @param JMDictEntity $entry The entry
     * 
     * @return void
     */
    public function missing_sense_element(JMDictEntity $entry)
    {
        $this->unimported_entries[] = array("Missing sense element", $entry);
    }
    
    /**
     * Log an missing binary element error
     * 
     * @param JMDictEntity $entry The entry
     * 
     * @return void
     */
    public function missing_binary_field(JMDictEntity $entry)
    {
        $this->unimported_entries[] = array("Missing binary field on one or more elements", $entry);
    }
    
    /**
     * Log an missing restr target element error
     * 
     * @param JMDictEntity $entry The entry
     * @param JMDictEntity $reading The reading which contains the restr
     * 
     * @return void
     */
    public function restr_target_not_found(JMDictEntity $entry, JMDictEntity $reading)
    {
        $this->warnings[] = array("Restr target not found", $entry, $reading);
    }
    
    /**
     * Log a new entry
     * 
     * @param JMDictEntity $entry The new entry
     * 
     * @return void
     */
    public function new_entry(JMDictEntity $new_entry)
    {
        $this->new_entries[] = $new_entry;
    }
    
    /**
     * Log an expired entry
     * 
     * @param JMDictEntity $expired_entry The expired entry
     * 
     * @return void
     */
    public function expired_entry(JMDictEntity $expired_entry)
    {
        $this->expired_entries[] = $expired_entry;
    }
    
    /**
     * Log a new kanji
     * 
     * @param JMDictEntity $new_kanji The new kanji
     * 
     * @return void
     */
    public function new_kanji(JMDictEntity $new_kanji)
    {
        $this->new_kanjis[] = $new_kanji;
    }
    
    /**
     * Log an expired kanji
     * 
     * @param JMDictEntity $expired_kanji The expired kanji
     * 
     * @return void
     */
    public function expired_kanji(JMDictEntity $expired_kanji)
    {
        $this->expired_kanjis[] = $expired_kanji;
    }
    
    /**
     * Log a new kanji
     * 
     * @param JMDictEntity $new_reading The new reading
     * 
     * @return void
     */
    public function new_reading(JMDictEntity $new_reading)
    {
        $this->new_readings[] = $new_reading;
    }
    
    /**
     * Log an expired reading
     * 
     * @param JMDictEntity $expired_reading The expired reading
     * 
     * @return void
     */
    public function expired_reading(JMDictEntity $expired_reading)
    {
        $this->expired_readings[] = $expired_reading;
    }
    
    /**
     * Log a new sense
     * 
     * @param JMDictEntity $new_sense The new sense
     * 
     * @return void
     */
    public function new_sense(JMDictEntity $new_sense)
    {
        $this->new_senses[] = $new_sense;
    }
    
    /**
     * Log an expired sense
     * 
     * @param JMDictEntity $expired_sense The expired sense
     * 
     * @return void
     */
    public function expired_sense(JMDictEntity $expired_sense)
    {
        $this->expired_senses[] = $expired_sense;
    }
    
    /**
     * Log a stagr target not found error
     * 
     * @param JMDictEntity $expired_sense The entry
     * @param JMDictEntity $sense The sense
     * 
     * @return void
     */
    public function stagr_target_not_found(JMDictEntity $entry, JMDictEntity $sense)
    {
        $this->warnings[] = array("Stagr target not found", $entry, $sense);
    }
    
    /**
     * Log a stagr target not found error
     * 
     * @param JMDictEntity $expired_sense The entry
     * @param JMDictEntity $sense The sense
     * 
     * @return void
     */
    public function stakr_target_not_found(JMDictEntity $entry, JMDictEntity $sense)
    {
        $this->warnings[] = array("Stagk target not found", $entry, $sense);
    }
    
    /**
     * Log an invalid reference type error
     * 
     * @param array $reference_type The reference data
     * @param string $msg The error message
     * 
     * @return void
     */
    public function invalid_reference_type(array $reference_type, string $msg = "Invalid reference type")
    {
        $this->warnings[] = array($msg, $reference_type);
    }
    
    /**
     * Log a k_and_r search failure
     * 
     * @param string $kanji_binary The kanji binary
     * @param string $reading_binary The reading binary
     * @param int $sense_index The sense index
     * 
     * @return void
     */
    public function k_and_r_search_failure(string $kanji_binary, string $reading_binary, int $sense_index = 0)
    {
        $this->warnings[] = array("Failed to find reference type: ", null, compact("kanji_binary", "reading_binary", "sense_index"));
    }
    
    /**
     * Log a k_or_r search failure
     * 
     * @param string $stringv The kanji or reading binary
     * @param int $sense_index The sense index
     * 
     * @return void
     */
    public function k_or_r_search_failure(string $stringv, int $sense_index = 0)
    {
        $this->warnings[] = array("Failed to find reference type: ", null, compact("stringv","sense_index"));
    }
    
    /**
     * Write log messages to file
     * 
     * @return void
     * 
     * @todo a lot of repetition here
     */
    public function flush()
    {
        $this->finish_time = time();
        $this->time_taken = $this->finish_time - $this->start_time;
        $this->net_time = sprintf("%dh:%dm:%ds", $this->time_taken / (60*60), ($this->time_taken % (60*60)) / 60, ($this->time_taken % (60*60)) % 60);
        
        $log_message = $this->new_line("Import Result");
        $log_message .= $this->new_line(date("F j, Y, g:i a [e]"));
        $log_message .= $this->new_line("Net Time: $this->net_time");
        $log_message .= $this->new_line();
        
        $libxml_warnings_count = count($this->libxml_warnings);
        $log_message .= $this->new_line("libxml warnings: $libxml_warnings_count");
        
        $unimported_entries_count = count($this->unimported_entries);
        $log_message .= $this->new_line("unimported entries: $unimported_entries_count");
        
        $warnings_count = count($this->warnings);
        $log_message .= $this->new_line("warnings: $warnings_count");
        
        $log_message .= $this->new_line();
        
        if($this->version_id > 1)
        {
            $new_entries_count = count($this->new_entries);
            $log_message .= $this->new_line("new entries: $new_entries_count");
            
            $expired_entries_count = count($this->expired_entries);
            $log_message .= $this->new_line("expired entries: $expired_entries_count");
            
            $new_kanjis_count = count($this->new_kanjis);
            $log_message .= $this->new_line("new kanjis: $new_kanjis_count");
            
            $expired_kanjis_count = count($this->expired_kanjis);
            $log_message .= $this->new_line("expired kanjis: $expired_kanjis_count");
            
            $new_readings_count = count($this->new_readings);
            $log_message .= $this->new_line("new readings: $new_readings_count");
            
            $expired_readings_count = count($this->expired_readings);
            $log_message .= $this->new_line("expired readings: $expired_readings_count");
            
            $new_senses_count = count($this->new_senses);
            $log_message .= $this->new_line("new senses: $new_senses_count");
            
            $expired_senses_count = count($this->expired_senses);
            $log_message .= $this->new_line("expired senses: $expired_senses_count");
        }
        
        $log_message .= $this->new_line();
        $log_message .= $this->new_line("---------------------------------------------------------------");
        $log_message .= $this->new_line();
        
        if($libxml_warnings_count > 0)
        {
            $i = 0;
            $log_message .= $this->new_line("libxml warnings:");
            $log_message .= $this->new_line();
            foreach($this->libxml_warnings as $warning)
            {
                $i++;
                $log_message .= $this->new_line("   libxml warning #$i:\n".$warning->message);
            }
        }
        
        if($unimported_entries_count > 0)
        {
            $i = 0;
            $log_message .= $this->new_line("unimported entries:");
            $log_message .= $this->new_line();
            foreach($this->unimported_entries as $unimported_entry)
            {
                $i++;
                $log_message .= $this->new_line("   unimported entry #$i:\n".print_r($unimported_entry,true));
            }
        }
        
        if($warnings_count > 0)
        {
            $i = 0;
            $log_message .= $this->new_line("warnings:");
            $log_message .= $this->new_line();
            foreach($this->warnings as $warning)
            {
                $i++;
                $log_message .= $this->new_line("   warning #$i:\n".print_r($warning,true));
            }
        }
        
        if($this->version_id > 1)
        {
            $log_message .= $this->new_line();
            
            if($new_entries_count > 0)
            {
                $i = 0;
                $log_message .= $this->new_line("new entries:");
                $log_message .= $this->new_line();
                foreach($this->new_entries as $new_entry)
                {
                    $i++;
                    $log_message .= $this->new_line("   new entry #$i:\n".print_r($new_entry,true));
                }
            }
            
            if($expired_entries_count > 0)
            {
                $i = 0;
                $log_message .= $this->new_line("expired entries:");
                $log_message .= $this->new_line();
                foreach($this->expired_entries as $expired_entry)
                {
                    $i++;
                    $log_message .= $this->new_line("   expired entry #$i:\n".print_r($expired_entry,true));
                }
            }
            
            if($new_kanjis_count > 0)
            {
                $i = 0;
                $log_message .= $this->new_line("new kanjis:");
                $log_message .= $this->new_line();
                foreach($this->new_kanjis as $new_kanji)
                {
                    $i++;
                    $log_message .= $this->new_line("   new kanji #$i:\n".print_r($new_kanji,true));
                }
            }
            
            if($expired_kanjis_count > 0)
            {
                $i = 0;
                $log_message .= $this->new_line("expired kanjis:");
                $log_message .= $this->new_line();
                foreach($this->expired_kanjis as $expired_kanji)
                {
                    $i++;
                    $log_message .= $this->new_line("   expired kanji #$i:\n".print_r($expired_kanji,true));
                }
            }
            
            if($new_readings_count > 0)
            {
                $i = 0;
                $log_message .= $this->new_line("new readings:");
                $log_message .= $this->new_line();
                foreach($this->new_readings as $new_reading)
                {
                    $i++;
                    $log_message .= $this->new_line("   new reading #$i:\n".print_r($new_reading,true));
                }
            }
            
            if($expired_readings_count > 0)
            {
                $i = 0;
                $log_message .= $this->new_line("expired readings:");
                $log_message .= $this->new_line();
                foreach($this->expired_readings as $expired_reading)
                {
                    $i++;
                    $log_message .= $this->new_line("   expired reading #$i:\n".print_r($expired_reading,true));
                }
            }
            
            if($new_senses_count > 0)
            {
                $i = 0;
                $log_message .= $this->new_line("new senses:");
                $log_message .= $this->new_line();
                foreach($this->new_senses as $new_sense)
                {
                    $i++;
                    $log_message .= $this->new_line("   new senses #$i:\n".print_r($new_sense,true));
                }
            }
            
            if($expired_senses_count > 0)
            {
                $i = 0;
                $log_message .= $this->new_line("expired senses:");
                $log_message .= $this->new_line();
                foreach($this->expired_senses as $expired_sense)
                {
                    $i++;
                    $log_message .= $this->new_line("   expired senses #$i:\n".print_r($expired_sense,true));
                }
            }
        }
        
        $this->logfile->write($log_message);
    }
    
    public function set_version_id(int $version_id)
    {
        $this->version_id = $version_id;
    }
}
