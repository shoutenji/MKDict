<?php

namespace MKDict\Logger;

abstract class Logger
{
    abstract public function flush();
    
    protected function newline($msg)
    {
        if(substr($msg, -1, 1) !== "\n")
        {
            $msg .= "\n";
        }
        return $msg;
    }
}
