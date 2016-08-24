<?php

namespace MKDict\FileResource;

interface FileResource
{
    public function read($num_bytes);
    
    public function write($text);
    
    public function open();
    
    public function close();
    
    public function feof();
}
