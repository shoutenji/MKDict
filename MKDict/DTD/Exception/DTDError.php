<?php

namespace MKDict\DTD\Exception;

use MKDict\Exception\FatalException;

class DTDError extends FatalException
{
    public $msg;
    
    public function __construct($msg)
    {
        $this->msg = $msg;
        parent::__construct(debug_backtrace());
    }
    
    public function get_message()
    {
        return "\n" . $this->colorize_text(ltrim(strrchr(__CLASS__, '\\'),'\\')) . "\n\nMessage:\n$this->msg" . "\nTrace:\n" . print_r($this->getTrace(),true);
    }
}
