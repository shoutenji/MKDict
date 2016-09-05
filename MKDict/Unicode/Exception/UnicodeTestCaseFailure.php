<?php

namespace MKDict\Unicode\Exception;

use MKDict\Exception\FatalException;

/**
 * Exception designated for Unicode failures
 * 
 * @see MKDict\Unicode\Unicode
 *
 * @author Taylor B <taylorbrontario@riseup.net>
 * 
 */
class UnicodeTestCaseFailure extends FatalException
{
    public $text_case;
    
    /**
     * Constructor
     * 
     * @param array $test_case an array containing params relating to the test case
     */
    public function __construct(array $test_case)
    {
        $this->test_case = $test_case;
        parent::__construct(debug_backtrace());
    }
    
    /**
     * @return string representation
     */
    public function get_message()
    {
        return "\n" . $this->colorize_text(ltrim(strrchr(__CLASS__, '\\'),'\\')) . "\n\nTest Case:\n".print_r($this->test_case,true)."\n\n" . "\nTrace:\n" . print_r($this->getTrace(),true);
    }
}
