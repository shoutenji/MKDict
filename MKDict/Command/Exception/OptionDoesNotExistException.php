<?php

namespace MKDict\Command\Exception;

use MKDict\Exception\FatalException;

/**
 * OptionDoesNotExistException
 * 
 * @author Taylor B <taylorbrontario@riseup.net>
 */
class OptionDoesNotExistException extends FatalException
{
    public $option;
    
    /**
     * Constructor
     * 
     * @param type $option
     */
    public function __construct($option)
    {
        $this->option = $option;
        parent::__construct(debug_backtrace());
    }
    
    /**
     * Get string message
     * 
     * @return string
     */
    public function get_message()
    {
        return "\n" . $this->colorize_text(ltrim(strrchr(__CLASS__, '\\'),'\\')) . "\n\nOption:\n" . print_r($this->option, true) . "\nTrace:\n" . print_r($this->getTrace(),true);
    }
}
