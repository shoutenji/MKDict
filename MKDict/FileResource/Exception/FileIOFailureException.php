<?php

namespace MKDict\FileResource\Exception;

use MKDict\FileResource\FileInfo;
use MKDict\Exception\FatalException;

class FileIOFailureException extends FatalException
{
    public $file_info;
    public $msg;
    
    public function __construct(FileInfo $file_info, $msg = "")
    {
        $this->file_info = $file_info;
        $this->msg = $msg;
        parent::__construct(debug_backtrace());
    }
    
    public function get_message()
    {
        return "\n" . $this->colorize_text(ltrim(strrchr(__CLASS__, '\\'),'\\')) . "\n\nMessage:\n". print_r($this->msg, true) . "\nTrace:\n" . print_r($this->get_stack_trace(),true);
    }
}
