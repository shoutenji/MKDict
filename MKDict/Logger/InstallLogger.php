<?php

namespace MKDict\Logger;

use MKDict\Logger\Logger;

/**
 * A logging class
 * 
 * @author Taylor B <taylorbrontario@riseup.net>
 * 
 * @todo make different log formats for first time imports and all successive imports
 */
class InstallLogger extends Logger
{
    /**
     * Constructor
     * 
     * @return void
     */
    public function __construct()
    {
        parent::__construct("install");
    }
    
    /**
     * Write log messages to file
     * 
     * @return void
     */
    public function flush()
    {
        $this->finish_time = time();
        $this->time_taken = $this->finish_time - $this->start_time;
        $this->net_time = sprintf("%dh:%dm:%ds", $this->time_taken / (60*60), ($this->time_taken % (60*60)) / 60, ($this->time_taken % (60*60)) % 60);
        
        $log_message = $this->new_line("Install Result: Successfull");
        $log_message .= $this->new_line(date("F j, Y, g:i a [e]"));
        $log_message .= $this->new_line("Net Time: $this->net_time");
        
        $this->logfile->write($log_message);
    }
}
