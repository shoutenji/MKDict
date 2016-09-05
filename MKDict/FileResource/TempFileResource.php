<?php

namespace MKDict\FileResource;

use MKDict\FileResource\ByteStreamFileResource;
use MKDict\FileResource\FileInfo;
use MKDict\Security\Security;

/**
 * A file resource class representing a plain text temporary file. These files can always be safely deleted afterwards.
 * 
 * @author Taylor B <taylorbrontario@riseup.net>
 */
class TempFileResource extends ByteStreamFileResource
{
    /**
     * Constructor
     */
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
