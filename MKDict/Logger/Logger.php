<?php

namespace MKDict\Logger;

use MKDict\FileResource\LogFileResource;

/**
 * A logging class
 * 
 * @author Taylor B <taylorbrontario@riseup.net>
 */
abstract class Logger
{
    /** @var int starting time */
    protected $start_time;
    
    /** @var int finish time */
    protected $finish_time;
    
    /** @var int total time taken */
    protected $net_time;
    
    /** @var LogFileResource The log file object*/
    protected $logfile;
    
    /** @var array The newest entries */
    protected $new_entries;
    
    /** @var array Entries that failed to be imported */
    protected $unimported_entries; 
    
    /** @var array Entries that are to be removed from the db */
    protected $expired_entries;
    
    /** @var array The new kanjis */
    protected $new_kanjis;
    
    /** @var array The kanjis that are to be removed from the db */
    protected $expired_kanjis;
    
    /** @var array The new readings */
    protected $new_readings;
    
    /** @var array The readings that are to be removed from the db */
    protected $expired_readings;
    
    /** @var array The new senses */
    protected $new_senses;
    
    /** @var array The senses that are to be removed from the db */
    protected $expired_senses;
    
    /** @var array libxml warnings*/
    protected $libxml_warnings;
    
    /** @var array Warnings */
    protected $warnings;
    
    /**
     * Write the logs contents to file
     */
    abstract public function flush();
    
    /**
     * Constructor
     * 
     * @param string $logfile_type either import or install 
     * 
     * @return void
     */
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
    
    /**
     * Outputs a line while handling newline characters
     * 
     * @param string $msg 
     * 
     * @return string
     */
    protected function new_line(string $msg = "")
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
