<?php

namespace MKDict\Database\Exception;

use MKDict\Exception\FatalException;

class DBConnectionError extends FatalException
{
    protected $msg;
    
    public function __construct($msg = "")
    {
        $this->msg = $msg;
        parent::__construct(debug_backtrace());
    }
    
    public function get_message()
    {
        return "\n" . $this->colorize_text(ltrim(strrchr(__CLASS__, '\\'),'\\')) . "\n\nMessage:\n$this->msg\n" . "\nTrace:\n" . print_r($this->get_stack_trace(),true);
    }
}
