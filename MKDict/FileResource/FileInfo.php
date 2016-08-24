<?php

namespace MKDict\FileResource;

use MKDict\FileResource\Url;
use MKDict\FileResource\Exception\BadFileInfoException;
use MKDict\FileResource\Exception\FileDoesNotExistException;

class FileInfo
{
    public $name;
    public $path;
    public $url;
    public $stream_context;
    public $mode;
    public $use_include;
    
    public function __construct(string $file_name = "", string $file_path = "", Url $url = null, array $stream_context = array(), string $mode = "", bool $use_include = false)
    {
        $this->name = $file_name;
        $this->path = $file_path;
        $this->url = $url;
        $this->stream_context = $stream_context;
        $this->mode = $mode;
        $this->use_include = $use_include;
    }
    
    public function has_stream_context()
    {
        return !empty($this->stream_context);
    }
    
    public function set_stream_context(array $stream_context)
    {
        $this->stream_context = $stream_context;
    }
    
    //todo check if stream is valid
    public function get_stream_context()
    {
        return stream_context_create($this->stream_context);
    }
    
    public function get_path_name()
    {
        if(!empty($this->path) && !empty($this->name))
        {
            return "$this->path/$this->name";
        }
        else if(!empty($this->url))
        {
            return $this->url->to_string();
        }
        else
        {
            throw new BadFileInfoException(debug_backtrace(), $this, "A FileInfo object must have at least either a full file path or url specified.");
        }
    }
    
    //note returns a string, not a url object!
    public function get_url()
    {
        if(!empty($this->url))
        {
            return $this->url->to_string();
        }
        else if(!empty($this->path) && !empty($this->name))
        {
            return "$this->path/$this->name";
        }
        else
        {
            throw new BadFileInfoException(debug_backtrace(), $this, "A FileInfo object must have at least either a full file path or url specified.");
        }
    }
    
    public function set_url(Url $url)
    {
        $this->url = $url;
    }
    
    public function set_mode(string $mode)
    {
        $this->mode = $mode;
    }
    
    public function get_mode()
    {
        if(empty($this->mode))
        {
            throw new BadFileInfoException(debug_backtrace(), $this, "A FileInfo object must a mode specified.");
        }
        
        return $this->mode;
    }
}
