<?php

namespace MKDict\FileResource;

use MKDict\FileResource\FileInfo;

interface FileResource
{
    public function read($num_bytes);
    
    public function write($text);
    
    public function open();
    
    public function close();
    
    public function feof();
    
    public function seek($offset, $whence);
    
    public function set_finfo(FileInfo $finfo);
    
    public function get_finfo();
    
    public function rewind();
}
