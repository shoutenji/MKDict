<?php

namespace MKDict\FileResource\Exception;

use MKDict\Exception\FatalException;
use MKDict\FileResource\FileInfo;

/**
 * BadFileInfoException
 * 
 * @author Taylor B <taylorbrontario@riseup.net>
 */
class BadFileInfoException extends FatalException
{
    public $file_info;
    public $msg;
    
    /**
     * Constructor
     * 
     * @param FileInfo $file_info
     * @param string $msg
     */
    public function __construct(FileInfo $file_info, $msg)
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
        return "\n" . $this->colorize_text(ltrim(strrchr(__CLASS__, '\\'),'\\')) . "\n\nMessage:\n$this->msg\n\nFileInfo:\n" . print_r($this->file_info, true) . "\nTrace:\n" . print_r($this->getTrace(),true);
    }
}
