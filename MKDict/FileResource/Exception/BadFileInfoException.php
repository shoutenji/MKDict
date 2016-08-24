<?php

namespace MKDict\FileResource\Exception;

use MKDict\Exception\FatalException;
use MKDict\FileResource\FileInfo;

class BadFileInfoException extends FatalException
{
    public $file_info;
    public $msg;
    
    public function __construct(array $stack_trace, FileInfo $file_info, $msg)
    {
        $this->file_info = $file_info;
        $this->msg = $msg;
        parent::__construct($stack_trace);
    }
    
    public function get_message()
    {
        return "\n" . $this->colorize_text(ltrim(strrchr(__CLASS__, '\\'),'\\')) . "\n\nMessage:\n$this->msg\n\nFileInfo:\n" . print_r($this->file_info, true) . "\nTrace:\n" . print_r($this->getTrace(),true);
    }
}
