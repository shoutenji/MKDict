<?php

namespace MKDict\FileResource;

use MKDict\FileResource\FileInfo;
use MKDict\FileResource\Downloadable;
use MKDict\FileResource\Exception\FileResourceNotAvailableException;
use MKDict\FileResource\Exception\FReadFailureException;
use MKDict\FileResource\Exception\FWriteFailureException;

class PlainTextFileResource implements FileResource, Downloadable
{
    protected $file_info;
    protected $file_handle;
    
    public function __construct(FileInfo $file_info)
    {
        $this->file_info = $file_info;
    }
    
    public function download_from(FileResource $file)
    {
        global $config;
        
        do
        {
            $this->write($file->read($config['HTTP_stream_chunk_size']));
        }
        while(!$file->feof());
    }
    
    public function read($num_bytes)
    {
        $result = @fread($this->file_handle, $num_bytes);
        
        if(false === $result)
        {
            throw new FReadFailureException(debug_backtrace(), $this->file_info);
        }
        
        return $result;
    }
    
    public function write($bytes)
    {
        $result = @fwrite($this->file_handle, $bytes);
        
        if(false === $result)
        {
            throw new FWriteFailureException(debug_backtrace(), $this->file_info);
        }
    }
    
    public function open()
    {
        if($this->file_info->has_stream_context())
        {
            $fhandle = fopen($this->file_info->get_path_name(), $this->file_info->mode, $this->file_info->use_include, $this->file_info->get_stream_context());
        }
        else
        {
            $fhandle = fopen($this->file_info->get_path_name(), $this->file_info->mode, $this->file_info->use_include);
        }
        
        if(false !== $fhandle)
        {
            $this->file_handle = $fhandle;
        }
        else
        {
            throw new FileResourceNotAvailableException(debug_backtrace(), $this->file_info);
        }
    }
    
    public function feof()
    {
        return \feof($this->file_handle);
    }
    
    public function close()
    {
        
    }
}
