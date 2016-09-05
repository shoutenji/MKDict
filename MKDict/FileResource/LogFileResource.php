<?php

namespace MKDict\FileResource;

use MKDict\FileResource\ByteStreamFileResource;
use MKDict\FileResource\FileInfo;
use MKDict\Security\Security;

/**
 * A file resource class representing a log file
 * 
 * @see MKDict\FileResource\FileResource
 *
 * @author Taylor B <taylorbrontario@riseup.net>
 */
class LogFileResource extends ByteStreamFileResource
{
    /**
     * Constructor
     * 
     * @param string $logfile_type
     */
    public function __construct(string $logfile_type = "")
    {
        global $config;
        
        if(!empty($logfile_type))
        {
            $logfile_type = "{$logfile_type}_";
        }
        
        $i = 0;
        do
        {
            $filename = sprintf("{$logfile_type}log_%03d", ++$i);
        }
        while(@file_exists("$config[log_dir]/$filename.log"));
        
        parent::__construct(new FileInfo("$filename.log", $config['log_dir'], null, array(), "w+"));
    }
}
