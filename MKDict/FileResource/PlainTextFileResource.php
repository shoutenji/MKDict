<?php

namespace MKDict\FileResource;

use MKDict\FileResource\FileInfo;
use MKDict\FileResource\Downloadable;
use MKDict\FileResource\Exception\FileResourceNotAvailableException;
use MKDict\FileResource\Exception\FReadFailureException;
use MKDict\FileResource\Exception\FWriteFailureException;
use MKDict\FileResource\Exception\FileTooLargeException;

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
            $bytes = $file->read($config['HTTP_stream_chunk_size']);
            if(@filesize($this->file_info->get_path_name()) + strlen($bytes) > $config['max_file_size'])
            {
                throw new FileTooLargeException(debug_backtrace(), $this->file_info);
            }
            $this->write();
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
        $this->file_handle = $this->file_info->get_handle();
    }
    
    public function feof()
    {
        if(empty($this->file_handle))
        {
            return true;
        }
        
        return \feof($this->file_handle);
    }
    
    public function close()
    {
        if(empty($this->file_handle))
        {
            return true;
        }
        
        return \fclose($this->file_handle);
    }
}
