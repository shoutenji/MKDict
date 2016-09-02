<?php

namespace MKDict\Logger;

use MKDict\FileResource\LogFileResource;

abstract class Logger
{
    protected $start_time;
    protected $finish_time;
    protected $net_time;
    
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
    
    abstract public function flush();
    
    public function __construct(string $logfile_type = "")
    {
        $this->start_time = time();
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
        
        $this->logfile = new LogFileResource($logfile_type);
        $this->logfile->open();
        
        $this->start_time = time();
    }
    
    protected function new_line($msg = "")
    {
        if(empty($msg))
        {
            return "\n";
        }
        
        if(substr($msg, -1, 1) !== "\n")
        {
            $msg .= "\n";
        }
        return $msg;
    }
}
