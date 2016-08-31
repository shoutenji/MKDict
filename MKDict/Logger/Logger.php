<?php

namespace MKDict\Logger;

abstract class Logger
{
    protected $start_time;
    protected $finish_time;
    protected $net_time;
    
    abstract public function flush();
    
    public function __construct()
    {
        $this->start_time = time();
    }
    
    protected function newline($msg)
    {
        if(substr($msg, -1, 1) !== "\n")
        {
            $msg .= "\n";
        }
        return $msg;
    }
}
