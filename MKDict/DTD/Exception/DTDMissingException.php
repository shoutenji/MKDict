<?php

namespace MKDict\DTD;

class DTDMissingException
{
    public $msg;
    
    public function __construct()
    {
        parent::__construct(debug_backtrace());
    }
    
    public function get_message()
    {
        return "\n" . $this->colorize_text(ltrim(strrchr(__CLASS__, '\\'),'\\'));
    }
}
