<?php

namespace MKDict\Command\Exception;

use MKDict\Exception\FatalException;

class OptionDoesNotExistException extends FatalException
{
    public $option;
    
    public function __construct(array $stack_trace, $option)
    {
        $this->option = $option;
        parent::__construct($stack_trace);
    }
    
    public function get_message()
    {
        return "\n" . $this->colorize_text(ltrim(strrchr(__CLASS__, '\\'),'\\')) . "\n\nOption:\n" . print_r($this->option, true) . "\nTrace:\n" . print_r($this->getTrace(),true);
    }
}
