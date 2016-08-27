<?php

namespace MKDict\FileResource;

use MKDict\FileResource\Downloadable;
use MKDict\FileResource\Exception\FReadFailureException;
use MKDict\FileResource\Exception\FWriteFailureException;
use MKDict\FileResource\Exception\FileTooLargeException;
use MKDict\FileResource\Exception\FileIOFailureException;

class GZFileResource implements FileResource, Downloadable
{
    public $file_info;
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
                throw new FileTooLargeException($this->file_info);
            }
            $this->write($bytes);
        }
        while(!$file->feof());
    }
    
    public function set_finfo(FileInfo $finfo)
    {
        $this->file_info = $finfo;
    }
    
    public function get_finfo()
    {
        return $this->file_info;
    }
    
    public function seek($offset, $whence)
    {
        if(0 > @gzseek($this->file_handle, $offset, $whence))
        {
            throw new FileIOFailureException($this->file_info);
        }
    }
    
    public function read($num_bytes)
    {
        $result = @gzread($this->file_handle, $num_bytes);
        
        if(false === $result)
        {
            throw new FReadFailureException($this->file_info);
        }
        
        return $result;
    }
    
    public function write($bytes)
    {
        $result = @gzwrite($this->file_handle, $bytes);
        
        if(false === $result)
        {
            throw new FWriteFailureException($this->file_info);
        }
    }
    
    public function open()
    {
        $this->file_handle = $this->file_info->get_handle(true);
    }
    
    public function feof()
    {
        if(empty($this->file_handle))
        {
            return true;
        }
        
        return \gzeof($this->file_handle);
    }
    
    public function close()
    {
        if(empty($this->file_handle))
        {
            return true;
        }
        
        return \gzclose($this->file_handle);
    }
    
    public function rewind()
    {
        if(empty($this->file_handle))
        {
            return false;
        }
        
        return @\gzrewind($this->file_handle);
    }
}
