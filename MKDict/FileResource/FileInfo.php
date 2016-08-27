<?php

namespace MKDict\FileResource;

use MKDict\FileResource\Url;
use MKDict\FileResource\Exception\BadFileInfoException;
use MKDict\FileResource\Exception\FileDoesNotExistException;
use MKDict\FileResource\Exception\FileIOFailureException;
use MKDict\FileResource\Exception\FileTooLargeException;
use MKDict\FileResource\Exception\FileNotWriteableException;
use MKDict\FileResource\Exception\FileNotReadableException;
use MKDict\FileResource\Exception\FileResourceNotAvailableException;

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
    protected $is_local;
    
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
        $this->is_local = true;
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
        else
        {
            throw new BadFileInfoException($this, "A FileInfo object must have at least either a full file path or url specified.");
        }
    }
    
    //note returns a string, not a url object!
    public function get_url()
    {
        if(!empty($this->url))
        {
            return $this->url->to_string();
        }
        else
        {
            throw new BadFileInfoException($this, "A FileInfo object must have at least either a full file path or url specified.");
        }
    }
    
    public function set_url(Url $url)
    {
        $this->url = $url;
        $this->is_local = false;
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
    
    public function get_option(int $option, $default_value)
    {
        switch($option)
        {
            case self::OPTION_IGNORE_BLANK_LINES:
                return (bool) ($this->ignore_blank_lines ?? $default_value);
                
            case self::OPTION_COMMENT_CHAR:
                return (string) ($this->comment_char ?? $value);
            
            case self::OPTION_VALUE_DELIMITER:
                return (string) ($this->value_delimiter ?? $value);
                
            default:
                return;
        }
    }
    
    public function is_local()
    {
        return $this->is_local;
    }
    
    //if the file doesn't meet certain conditions, you can't get a handle for it
    public function get_handle(bool $is_compressed = false)
    {
        global $config;
        
        if(empty($this->mode))
        {
            throw new BadFileInfoException($this, "A FileInfo object must a mode specified.");
        }
        
        //if the file is remote, then we don't have to do any filesize or permissions checking
        if($this->is_local())
        {
            $file_path = $this->get_path_name();
            $exists = @file_exists($file_path);
            
            switch($this->mode)
            {
                case 'r':
                case 'rt':
                case 'rb':
                    if(!$exists)
                    {
                        throw new FileDoesNotExistException($this);
                    }
                    if(!@is_readable($file_path))
                    {
                        throw new FileNotReadableException($this);
                    }
                    if(false === $size = @filesize($file_path))
                    {
                        throw new FileIOFailureException($this);
                    }
                    else
                    {
                        if($size > $config['max_file_size'])
                        {
                            throw new FileTooLargeException($this);
                        }
                    }
                    break;
                    
                case 'w':
                case 'wt':
                case 'wb':
                case 'a':
                case 'at':
                case 'ab':
                case 'x':
                case 'xt':
                case 'xb':
                case 'c':
                case 'ct':
                case 'cb':
                    if($exists && !@is_writeable($file_path))
                    {
                        throw new FileNotWriteableException($this);
                    }
                    break;
                    
                case 'r+':
                    if(!$exists)
                    {
                        throw new FileDoesNotExistException($this);
                    }
                case 'w+':
                case 'w+t':
                case 'w+b':
                case 'a+':
                case 'a+t':
                case 'a+b':
                case 'x+':
                case 'x+t':
                case 'x+b':
                case 'c+':
                case 'c+t':
                case 'c+b':
                    if($exists && !@is_readable($file_path))
                    {
                        throw new FileNotReadableException($this);
                    }
                    
                    if($exists && !@is_writeable($file_path))
                    {
                        throw new FileNotWriteableException($this);
                    }
                    if($exists)
                    {
                        if(false === $size = @filesize($file_path))
                        {
                            throw new FileIOFailureException($this);
                        }
                        else
                        {
                            if($size > $config['max_file_size'])
                            {
                                throw new FileTooLargeException($this);
                            }
                        }
                    }
                    break;
                
                default:
                    throw new BadFileInfoException($this, "Bad file mode specified.");
            }
        }
        else
        {
            $file_path = $this->get_url();
        }
        
        if($this->has_stream_context())
        {
            if($is_compressed)
            {
                $handle = @\gzopen($file_path, $this->mode, $this->use_include, $this->get_stream_context());
            }
            else
            {
                $handle = @\fopen($file_path, $this->mode, $this->use_include, $this->get_stream_context());
            }
        }
        else
        {
            if($is_compressed)
            {
                $handle = @\gzopen($file_path, $this->mode, $this->use_include);
            }
            else
            {
                $handle = @\fopen($file_path, $this->mode, $this->use_include);
            }
        }
        
        if(false === $handle)
        {
            throw new FileResourceNotAvailableException($this);
        }
        
        return $handle;
    }
}
