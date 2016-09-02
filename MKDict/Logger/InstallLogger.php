<?php

namespace MKDict\Logger;

use MKDict\Logger\Logger;

class InstallLogger extends Logger
{
    public function __construct()
    {
        parent::__construct("install");
    }
    
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
