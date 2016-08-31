<?php

namespace MKDict\Unicode\Exception;

use MKDict\Exception\FatalException;

class UnicodeTestCaseFailure extends FatalException
{
    public $text_case;
    
    public function __construct(array $stack_trace, array $test_case)
    {
        $this->test_case = $test_case;
        parent::__construct($stack_trace);
    }
    
    public function get_message()
    {
        return "\n" . $this->colorize_text(ltrim(strrchr(__CLASS__, '\\'),'\\')) . "\n\nTest Case:\n".print_r($this->test_case,true)."\n\n" . "\nTrace:\n" . print_r($this->getTrace(),true);
    }
}
