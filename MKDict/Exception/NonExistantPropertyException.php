<?php

namespace MKDict\Exception;

use MKDict\Exception\FatalException;

class NonExistantPropertyException extends FatalException
{
    protected $msg;
    
    public function __construct($msg)
    {
        $this->msg = $msg;
        parent::__construct();
    }
    
    public function get_message()
    {
        return "\n" . $this->colorize_text(ltrim(strrchr(__CLASS__, '\\'),'\\')) . "\n\Message:\n" . print_r($this->msg, true) . "\nTrace:\n" . print_r($this->getTrace(),true);
    }
}
