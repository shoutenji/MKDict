<?php

namespace MKDict\FileResource\Exception;

use MKDict\FileResource\FileInfo;
use MKDict\Exception\FatalException;

/**
 * FileIOFailureException
 * 
 * @author Taylor B <taylorbrontario@riseup.net>
 */
class FileIOFailureException extends FatalException
{
    public $file_info;
    public $msg;
    
    /**
     * Constructor
     * 
     * @param FileInfo $file_info
     * @param string $msg
     */
    public function __construct(FileInfo $file_info, $msg = "")
    {
        $this->file_info = $file_info;
        $this->msg = $msg;
        parent::__construct(debug_backtrace());
    }
    
    /**
     * @return get string representation
     */
    public function get_message()
    {
        return "\n" . $this->colorize_text(ltrim(strrchr(__CLASS__, '\\'),'\\')) . "\n\nMessage:\n". print_r($this->msg, true) . "\nTrace:\n" . print_r($this->get_stack_trace(),true);
    }
}
