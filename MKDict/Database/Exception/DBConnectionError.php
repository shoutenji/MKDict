<?php

namespace MKDict\Database\Exception;

use MKDict\Exception\FatalException;

//todo this class hasn't been properly refactored
class DBConnectionError extends FatalException
{
    public $table;
    
    public function __construct()
    {
        $this->table = $table;
        parent::__construct(debug_backtrace());
    }
    
    public function get_message()
    {
        return "\n" . $this->colorize_text(ltrim(strrchr(__CLASS__, '\\'),'\\')) . "\nTrace:\n" . print_r($this->get_stack_trace(),true);
    }
}
