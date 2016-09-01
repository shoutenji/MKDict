<?php


namespace MKDict\Logger;

use MKDict\Logger\Logger;
use MKDict\Database\JMDictEntity;
use MKDict\FileResource\LogFileResource;

class ImportLogger extends Logger
{
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
    
    public function new_entry(JMDictEntity $new_entry)
    {
        $this->new_entries[] = $new_entry;
    }
    
    public function expired_entry(JMDictEntity $expired_entry)
    {
        $this->expired_entries[] = $expired_entry;
    }
    
    public function new_kanji(JMDictEntity $new_kanji)
    {
        $this->new_kanjis[] = $new_kanji;
    }
    
    public function expired_kanji(JMDictEntity $expired_kanji)
    {
        $this->expired_kanjis[] = $expired_kanji;
    }
    
    public function new_reading(JMDictEntity $new_reading)
    {
        $this->new_readings[] = $new_reading;
    }
    
    public function expired_reading(JMDictEntity $expired_reading)
    {
        $this->expired_readings[] = $expired_reading;
    }
    
    public function new_sense(JMDictEntity $new_sense)
    {
        $this->new_senses[] = $new_sense;
    }
    
    public function expired_sense(JMDictEntity $expired_sense)
    {
        $this->expired_senses[] = $expired_sense;
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
    
    //todo a lot of repetition here
    public function flush()
    {
        $this->finish_time = time();
        $this->time_taken = $this->finish_time - $this->start_time;
        $this->net_time = sprintf("%dh:%dm:%ds", $this->time_taken / (60*60), ($this->time_taken % (60*60)) / 60, ($this->time_taken % (60*60)) % 60);
        
        $log_message = $this->new_line("Import Result");
        $log_message .= $this->new_line(date("F j, Y, g:i a [e]"));
        $log_message .= $this->new_line("Net Time: $this->net_time");
        
        $libxml_warnings_count = count($this->libxml_warnings);
        $log_message .= $this->new_line("libxml warnings: $libxml_warnings_count");
        
        $unimported_entries_count = count($this->unimported_entries);
        $log_message .= $this->new_line("unimported entries: $unimported_entries_count");
        
        $warnings_count = count($this->warnings);
        $log_message .= $this->new_line("warnings: $warnings_count");
        
        if($this->version_id > 1)
        {
            $new_entries_count = count($this->new_entries);
            $log_message .= $this->new_line("new entries: $new_entries_count");
            
            $expired_entries_count = count($this->expired_entries);
            $log_message .= $this->new_line("expired entries: $expired_entries_count");
            
            $new_kanjis_count = count($this->new_kanjis);
            $log_message .= $this->new_line("new kanjis: $new_kanjis_count");
            
            $expired_kanjis_count = count($this->expired_kanjis);
            $log_message .= $this->new_line("expired entries: $expired_kanjis_count");
            
            $new_readings_count = count($this->new_readings);
            $log_message .= $this->new_line("new reading: $new_readings_count");
            
            $expired_readings_count = count($this->expired_readings);
            $log_message .= $this->new_line("expired readings: $expired_readings_count");
            
            $new_senses_count = count($this->new_senses);
            $log_message .= $this->new_line("new senses: $new_senses_count");
            
            $expired_senses_count = count($this->expired_senses);
            $log_message .= $this->new_line("expired sense: $expired_senses_count");
        }
        
        $log_message .= $this->new_line("---------------------------------------------------------------");
        
        if($libxml_warnings_count > 0)
        {
            $i = 0;
            $log_message .= $this->new_line("libxml warnings:");
            foreach($this->libxml_warnings as $warning)
            {
                $i++;
                $log_message .= $this->new_line("   libxml warning #$i:".$warning->message);
            }
        }
        
        if($unimported_entries_count > 0)
        {
            $i = 0;
            $log_message .= $this->new_line("unimported entries:");
            foreach($this->unimported_entries as $unimported_entry)
            {
                $i++;
                $log_message .= $this->new_line("   unimported entry #$i:".print_r($unimported_entry,true));
            }
        }
        
        if($warnings_count > 0)
        {
            $i = 0;
            $log_message .= $this->new_line("warnings:");
            foreach($this->warnings as $warning)
            {
                $i++;
                $log_message .= $this->new_line("   warning #$i:".print_r($warning,true));
            }
        }
        
        if($this->version_id > 1)
        {
            if($new_entries_count > 0)
            {
                $i = 0;
                $log_message .= $this->new_line("new entries:");
                foreach($this->new_entries as $new_entry)
                {
                    $i++;
                    $log_message .= $this->new_line("   new entry #$i:".print_r($new_entry,true));
                }
            }
            
            if($expired_entries_count > 0)
            {
                $i = 0;
                $log_message .= $this->new_line("expired entries:");
                foreach($this->expired_entries as $expired_entry)
                {
                    $i++;
                    $log_message .= $this->new_line("   expired entry #$i:".print_r($expired_entry,true));
                }
            }
            
            if($new_kanjis_count > 0)
            {
                $i = 0;
                $log_message .= $this->new_line("new kanjis:");
                foreach($this->new_kanjis as $new_kanji)
                {
                    $i++;
                    $log_message .= $this->new_line("   new kanji #$i:".print_r($new_kanji,true));
                }
            }
            
            if($expired_kanjis_count > 0)
            {
                $i = 0;
                $log_message .= $this->new_line("expired kanjis:");
                foreach($this->expired_kanjis as $expired_kanji)
                {
                    $i++;
                    $log_message .= $this->new_line("   expired kanji #$i:".print_r($expired_kanji,true));
                }
            }
            
            if($new_readings_count > 0)
            {
                $i = 0;
                $log_message .= $this->new_line("new readings:");
                foreach($this->new_readings as $new_reading)
                {
                    $i++;
                    $log_message .= $this->new_line("   new reading #$i:".print_r($new_reading,true));
                }
            }
            
            if($expired_readings_count > 0)
            {
                $i = 0;
                $log_message .= $this->new_line("expired readings:");
                foreach($this->expired_readings as $expired_reading)
                {
                    $i++;
                    $log_message .= $this->new_line("   expired reading #$i:".print_r($expired_reading,true));
                }
            }
            
            if($new_senses_count > 0)
            {
                $i = 0;
                $log_message .= $this->new_line("new senses:");
                foreach($this->new_senses as $new_sense)
                {
                    $i++;
                    $log_message .= $this->new_line("   new senses #$i:".print_r($new_sense,true));
                }
            }
            
            if($expired_senses_count > 0)
            {
                $i = 0;
                $log_message .= $this->new_line("expired senses:");
                foreach($this->expired_senses as $expired_sense)
                {
                    $i++;
                    $log_message .= $this->new_line("   expired senses #$i:".print_r($expired_sense,true));
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
