<?php

namespace MKDict\FileResource;

use MKDict\FileResource\PlainTextFileResource;
use MKDict\FileResource\FileInfo;
use MKDict\Security\Security;

class TempFileResource extends PlainTextFileResource
{
    public function __construct()
    {
        global $config;
        
        do
        {
            $filename = Security::weak_random_string();
        }
        while(@file_exists("$config[tmp_dir]/$filename"));
        
        parent::__construct(new FileInfo($filename, $config['tmp_dir'], null, array(), "w+"));
    }
}
