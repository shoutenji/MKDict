<?php

namespace MKDict\Exception;

abstract class FatalException extends \Exception
{
    protected $stack_trace;
    
    public abstract function get_message();
    
    public function __construct($stack_trace)
    {
        $this->stack_trace = $stack_trace;
    }
    
    public function get_stack_trace()
    {
        return $this->stack_trace;
    }
    
    public function colorize_text($text, $style = "error")
    {
        switch($style)
        {
            case "error":
                return "\033[37;41m$text\033[0m";
        }
    }
}
