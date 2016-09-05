<?php

namespace MKDict\FileResource\Exception;

use MKDict\FileResource\FileInfo;
use MKDict\Exception\FatalException;

/**
 * FileDoesNotExistException
 * 
 * @author Taylor B <taylorbrontario@riseup.net>
 */
class FileDoesNotExistException extends FatalException
{
    public $file_info;
    
    /**
     * Constructor
     * 
     * @param FileInfo $file_info
     */
    public function __construct(FileInfo $file_info)
    {
        $this->file_info = $file_info;
        parent::__construct(debug_backtrace());
    }
    
    /**
     * @return get string representation
     */
    public function get_message()
    {
        return "\n" . $this->colorize_text(ltrim(strrchr(__CLASS__, '\\'),'\\')) . "\n\n" . print_r($this->file_info, true) . "\nTrace:\n" . print_r($this->get_stack_trace(),true);
    }
}
