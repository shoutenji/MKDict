<?php

namespace MKDict\FileResource;

use MKDict\FileResource\FileInfo;
use MKDict\FileResource\Downloadable;
use MKDict\FileResource\Exception\FileResourceNotAvailableException;
use MKDict\FileResource\Exception\FReadFailureException;
use MKDict\FileResource\Exception\FWriteFailureException;
use MKDict\FileResource\Exception\FileTooLargeException;
use MKDict\FileResource\Exception\FileIOFailureException;

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
        if(0 > @fseek($this->file_handle, $offset, $whence))
        {
            throw new FileIOFailureException($this->file_info);
        }
    }
    
    public function read($num_bytes)
    {
        $bytes = @fread($this->file_handle, $num_bytes);
        
        if(false === $bytes)
        {
            throw new FReadFailureException($this->file_info);
        }
        
        return $bytes;
    }
    
    public function write($bytes)
    {
        $result = @fwrite($this->file_handle, $bytes);
        
        if(false === $result)
        {
            throw new FWriteFailureException($this->file_info);
        }
    }
    
    public function open()
    {
        $this->file_handle = $this->file_info->get_handle();
    }
    
    //todo create a csv interface and unify he csv and temp file resource classes
    public function fgets()
    {
        if(!empty($this->file_handle))
        {
            return @fgets($this->file_handle);
        }
        
        return false;
    }
   
    public function unlink()
    {
        if(!empty($this->file_handle))
        {
            $this->close($this->file_handle);
            return @unlink($this->file_info->get_path_name());
        }
    }
    
    public function feof()
    {
        if(empty($this->file_handle))
        {
            return true;
        }
        
        return @feof($this->file_handle);
    }
    
    public function close()
    {
        if(empty($this->file_handle))
        {
            return true;
        }
        
        return @fclose($this->file_handle);
    }
    
    public function rewind()
    {
        if(empty($this->file_handle))
        {
            return false;
        }
        
        return @rewind($this->file_handle);
    }
}
