<?php

namespace MKDict\FileResource;

use MKDict\FileResource\PlainTextFileResource;
use MKDict\FileResource\FileInfo;
use MKDict\Security\Security;

class LogFileResource extends PlainTextFileResource
{
    public function __construct()
    {
        global $config;
        
        do
        {
            $filename = Security::weak_random_string();
        }
        while(@file_exists("$config[log_dir]/$filename.log"));
        
        parent::__construct(new FileInfo("$filename.log", $config['log_dir'], null, array(), "w+"));
    }
}
