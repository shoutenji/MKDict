<?php

namespace MKDict\Exception;

use MKDict\Exception\FatalException;

/**
 * LibXMLError
 * 
 * @author Taylor B <taylorbrontario@riseup.net>
 */
class LibXMLError extends FatalException
{
    protected $error;
    
    /**
     * Constructor
     * 
     * @param \libXMLError $error
     */
    public function __construct(\libXMLError $error)
    {
        $this->error = $error;
        parent::__construct(debug_backtrace());
    }
    
    /**
     * Get string message
     * 
     * @return string
     */
    public function get_message()
    {
        return "\n" . $this->colorize_text(ltrim(strrchr(__CLASS__, '\\'),'\\')) . "\n\libxml error:\n" . print_r($this->error->message, true) . "\nTrace:\n" . print_r($this->getTrace(),true);
    }
}
