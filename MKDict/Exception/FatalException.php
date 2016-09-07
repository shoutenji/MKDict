<?php

namespace MKDict\Exception;

/**
 * FatalException
 * 
 * @author Taylor B <taylorbrontario@riseup.net>
 */
abstract class FatalException extends \Exception
{
    protected $stack_trace;
    
    /**
     * @return string
     */
    public abstract function get_message();
    
    /**
     * Constructor
     * 
     * @param array $stack_trace The returned value of debug_backtrace()
     */
    public function __construct(array $stack_trace)
    {
        $this->stack_trace = $stack_trace;
    }
    
    /**
     * Return the back trace
     * 
     * @return array
     */
    public function get_stack_trace()
    {
        return $this->stack_trace;
    }
    
    /**
     * Colorize text for terminal output by wrapping it in the appropriate escape seqeunces
     * 
     * @param string $text
     * @param string $style
     * 
     * @return string The colorized text
     */
    public function colorize_text(string $text, string $style = "error")
    {
        switch($style)
        {
            case "error":
                return "\033[37;41m$text\033[0m";
        }
    }
}
