<?php

namespace MKDict\FileResource\Exception;

use MKDict\FileResource\FileInfo;
use MKDict\Exception\FatalException;

class FileDoesNotExistException extends FatalException
{
    public $file_info;
    
    public function __construct(array $stack_trace, FileInfo $file_info)
    {
        $this->file_info = $file_info;
        parent::__construct($stack_trace);
    }
    
    public function get_message()
    {
        return "\n" . $this->colorize_text(ltrim(strrchr(__CLASS__, '\\'),'\\')) . "\n\n" . print_r($this->file_info, true) . "\nTrace:\n" . print_r($this->getTrace(),true);
    }
}
