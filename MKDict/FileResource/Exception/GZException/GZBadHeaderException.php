<?php


namespace MKDict\FileResource\Exception\GZException;

use MKDict\Exception\FatalException;
use MKDict\FileResource\FileInfo;

/**
 * GZBadHeaderException
 * 
 * @author Taylor B <taylorbrontario@riseup.net>
 */
class GZBadHeaderException extends FatalException
{
    public $file_info;
    public $msg;
    
    /**
     * Constructor
     * 
     * @param FileInfo $file_info
     */
    public function __construct(FileInfo $file_info, string $msg)
    {
        $this->file_info = $file_info;
        $this->msg = $msg;
        parent::__construct(debug_backtrace());
    }
    
    /**
     * @return string representation
     */
    public function get_message()
    {
        return "\n" . $this->colorize_text(ltrim(strrchr(__CLASS__, '\\'),'\\')) . "\n\nMessage:\n$this->msg\n\nFileInfo:\n" . print_r($this->file_info, true) . "\nTrace:\n" . print_r($this->getTrace(),true);
    }
}
