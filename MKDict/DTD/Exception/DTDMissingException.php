<?php

namespace MKDict\DTD;

/**
 * DTDMissingException
 * 
 * @author Taylor B <taylorbrontario@riseup.net>
 */
class DTDMissingException
{
    public $msg;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct(debug_backtrace());
    }
    
    /**
     * Get string message
     * 
     * @return string
     */
    public function get_message()
    {
        return "\n" . $this->colorize_text(ltrim(strrchr(__CLASS__, '\\'),'\\'));
    }
}
