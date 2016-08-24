<?php

namespace MKDict\FileResource;

use MKDict\FileResource\Url;
use MKDict\FileResource\Exception\BadFileInfoException;
use MKDict\FileResource\Exception\FileDoesNotExistException;
use MKDict\FileResource\Exception\FileIOFailureException;
use MKDict\FileResource\Exception\FileTooLargeException;
use MKDict\FileResource\Exception\FileNotWriteableException;
use MKDict\FileResource\Exception\FileNotReadableException;

class FileInfo
{
    protected $name;
    protected $path;
    protected $url;
    protected $stream_context;
    protected $mode;
    protected $use_include;
    protected $ignore_blank_lines;
    protected $comment_char;
    protected $value_delimiter;
    
    const OPTION_IGNORE_BLANK_LINES = 1;
    const OPTION_COMMENT_CHAR = 2;
    const OPTION_VALUE_DELIMITER = 3;
    
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
        $this->mode = strtolower($mode);
    }
    
    public function set_option(int $option, $value)
    {
        switch($option)
        {
            case self::OPTION_IGNORE_BLANK_LINES:
                $this->ignore_blank_lines = (bool) $value;
                return;
                
            case self::OPTION_COMMENT_CHAR:
                $this->comment_char = (string) $value;
                return;
            
            case self::OPTION_VALUE_DELIMITER:
                $this->value_delimiter = (string) $value;
                return;
                
            default:
                return;
        }
    }
    
    public function is_local()
    {
        $path_name = $this->get_path_name();
        //this equality will hold IFF the url property is empty and the path components are not
        return !empty($path_name) && $path_name === $this->get_url();
    }
    
    //if the file doesn't meet certain conditions, you can't get a handle for it
    public function get_handle()
    {
        global $config;
        
        $path_name = $this->get_path_name();
        
        //if this is a local file, we should check if the file exists and ensure it has a reasonable size. file_exists() returns false for urls
        if($this->is_local())
        {
            if(!@file_exists($path_name))
            {
                throw new FileDoesNotExistException(debug_backtrace(), $this);
            }
            
            if(false === $size = @filesize($path_name))
            {
                throw new FileIOFailureException(debug_backtrace(), $this);
            }
            else
            {
                if($size > $config['max_file_size_in_ram'])
                {
                    throw new FileTooLargeException(debug_backtrace(), $this);
                }
            }
            
            if(empty($this->mode))
            {
                throw new BadFileInfoException(debug_backtrace(), $this, "A FileInfo object must a mode specified.");
            }
            
            switch($this->mode)
            {
                case 'r':
                    if(!@is_readable($path_name))
                    {
                        throw new FileNotReadableException(debug_backtrace(), $this);
                    }
                    break;
                    
                case 'w':
                case 'a':
                case 'x':
                case 'c':
                    if(!is_writeable($path_name))
                    {
                        throw new FileNotWriteableException(debug_backtrace(), $this);
                    }
                    break;
                    
                case 'r+':
                case 'w+':
                case 'a+':
                case 'x+':
                case 'c+':
                    if(!@is_readable($path_name))
                    {
                        throw new FileNotReadableException(debug_backtrace(), $this);
                    }
                    
                    if(!is_writeable($path_name))
                    {
                        throw new FileNotWriteableException(debug_backtrace(), $this);
                    }
                    break;
                
                default:
                    throw new BadFileInfoException(debug_backtrace(), $this, "Bad file mode specified.");
            }
        }
        
        if($this->has_stream_context())
        {
            $handle = fopen($path_name, $this->mode, $this->use_include, $this->get_stream_context());
        }
        else
        {
            $handle = fopen($path_name, $this->mode, $this->use_include);
        }
        
        if(false === $handle)
        {
            throw new FileResourceNotAvailableException(debug_backtrace(), $this->file_info);
        }
        
        return $handle;
    }
}
