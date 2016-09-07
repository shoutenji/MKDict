<?php

namespace MKDict\DTD\Exception;

use MKDict\Exception\FatalException;

/**
 * DTDError
 * 
 * @author Taylor B <taylorbrontario@riseup.net>
 */
class DTDError extends FatalException
{
    public $msg;
    
    /**
     * Constructor 
     * @param string $msg
     */
    public function __construct(string $msg)
    {
        $this->msg = $msg;
        parent::__construct(debug_backtrace());
    }
    
    /**
     * Get string message
     * 
     * @return string
     */
    public function get_message()
    {
        return "\n" . $this->colorize_text(ltrim(strrchr(__CLASS__, '\\'),'\\')) . "\n\nMessage:\n$this->msg" . "\nTrace:\n" . print_r($this->getTrace(),true);
    }
}
