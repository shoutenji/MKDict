<?php

namespace MKDict\FileResource\Exception;

use MKDict\FileResource\FileInfo;
use MKDict\Exception\FatalException;

class FileNotReadableException extends FatalException
{
    public $file_info;
    
    public function __construct(FileInfo $file_info)
    {
        $this->file_info = $file_info;
        parent::__construct(debug_backtrace());
    }
    
    public function get_message()
    {
        return "\n" . $this->colorize_text(ltrim(strrchr(__CLASS__, '\\'),'\\')) . "\n\n". print_r($this->file_info, true) . "\nTrace:\n" . print_r($this->getTrace(),true);
    }
}
