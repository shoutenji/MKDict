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

/**
 * Wraps information representing a file, similar to Java's File class.
 * 
 * @todo throw error if file is a directory (we are not wrapping directorys)
 * @todo disallow files which are links
 * 
 * @author Taylor B <taylorbrontario@riseup.net>
 */
class FileInfo
{
    /** @var string The file name, including the extension, and excluding the path */
    protected $name;
    
    /** @var string The path of the file excluding leading slash */
    protected $path;
    
    /** @var Url A Url object to wrap remote files */
    protected $url;
    
    /** @var array An array of stream options as would be passed to stream_context_create() */
    protected $stream_context;
    
    /** @var string The file mode */
    protected $mode;
    
    /** @var bool Whether or not to use the include path in searching for local files */
    protected $use_include;
    
     /**
      * Vars related to CSV file types
      * 
      * @var string $ignore_blank_lines     Ignore blank lines during process
      * @var string $comment_char           Ignore lines and starting with this string and truncate tails of lines to the last instance this character
      * @var string $value_delimiter        Value delimiter
      */
    protected $ignore_blank_lines;
    protected $comment_char;
    protected $value_delimiter;
    
     /**
      * Option types
      */
    const OPTION_IGNORE_BLANK_LINES = 1;
    const OPTION_COMMENT_CHAR = 2;
    const OPTION_VALUE_DELIMITER = 3;
    
    /** @var bool Flag to indicate if file is local. Defaults to true */
    protected $is_local;
    
    /**
     * Constructor.
     *
     * @param string $file_name   The file name, including the extension, and excluding the path
     * @param string $file_path   The path of the file excluding leading slash
     * @param Url $url            A Url object to wrap remote files
     * @param array $stream_context   An array of stream options as would be passed to stream_context_create()
     * @param string $mode            The file mode
     * @param bool $use_include       Whether or not to use the include path in searching for local files
     */
    public function __construct(string $file_name = "", string $file_path = "", Url $url = null, array $stream_context = array(), string $mode = "", bool $use_include = false)
    {
        $this->name = $file_name;
        $this->path = rtrim($file_path, "/");
        $this->url = $url;
        $this->stream_context = $stream_context;
        $this->mode = $mode;
        $this->use_include = $use_include;
        $this->is_local = true;
    }
    
    /**
     * Check if this FileInfo object has a stream context
     *
     * @return bool True if this FileInfo object has a stream context false otherwise
     */
    public function has_stream_context()
    {
        return !empty($this->stream_context);
    }
    
    /**
     * Set the stream context
     * 
     * @param array $stream_context 
     *
     * @return void
     */
    public function set_stream_context(array $stream_context)
    {
        $this->stream_context = $stream_context;
    }
    
    /**
     * Create and get a stream context
     * 
     * @return resource Resource returned from PHP's stream_context_create()
     */
    public function get_stream_context()
    {
        return stream_context_create($this->stream_context);
    }
    
    /**
     * Get the fully qualified path name
     * 
     * @return string The path to the file excluding trailing slash
     * 
     * @throws BadFileInfoException if $path and $name are empty
     */
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
    
    /**
     * Get a string representation of this FileInfo's Url
     * 
     * @return string The url as a string
     * 
     * @throws BadFileInfoException if the url is empty
     */
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
    
    /**
     * Set the Url
     * 
     * @param Url $url The Url object
     * 
     * @return void
     */
    public function set_url(Url $url)
    {
        $this->url = $url;
        $this->is_local = false;
    }
    
    /**
     * Set the mode
     * 
     * @param string $mode The file mode
     * 
     * @return void
     */
    public function set_mode(string $mode)
    {
        $this->mode = strtolower($mode);
    }
    
    /**
     * Set one of this FileInfo's options
     * 
     * @param int $option The optiont type. See constants above
     * 
     * @return void
     */
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
    
    /**
     * Get one of this FileInfo's options
     * 
     * @param int $option The optiont type. See constants above
     * @param bool|string $default The value to return if the option has not been set
     * 
     * @return bool|string
     */
    public function get_option(int $option, $default_value)
    {
        switch($option)
        {
            case self::OPTION_IGNORE_BLANK_LINES:
                return (bool) ($this->ignore_blank_lines ?? $default_value);
                
            case self::OPTION_COMMENT_CHAR:
                return (string) ($this->comment_char ?? $default_value);
            
            case self::OPTION_VALUE_DELIMITER:
                return (string) ($this->value_delimiter ?? $default_value);
                
            default:
                return;
        }
    }
    
    /**
     * Check if this FilInfo object represents a local file
     * 
     * @return bool True if file is local
     */
    public function is_local()
    {
        return $this->is_local;
    }
    
    /**
     * Get a native file resource to the file this FileInfo represents if and only if this FileInfo object contains valid information
     * 
     * @param bool $is_compressed Whether or not this file is compressed
     * 
     * @return resource A native file resource
     * 
     * @throws BadFileInfoException If any FileInfo fields are invalid, such as the mode
     * @throws FileDoesNotExistException If the file does not exist (must be local)
     * @throws FileNotReadableException If the file is not readable (must be local)
     * @throws FileNotWriteableException If the file is not writeable (must be local)
     * @throws FileIOFailureException 
     * @throws FileTooLargeException If file is too large to read 
     * @throws FileResourceNotAvailableException if PHP's fopen() or gzopen() failure
     */
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
            $exists = file_exists($file_path);
            
            switch($this->mode)
            {
                case 'r':
                case 'rt':
                case 'rb':
                    if(!$exists)
                    {
                        throw new FileDoesNotExistException($this);
                    }
                    
                    if(!is_readable($file_path))
                    {
                        throw new FileNotReadableException($this);
                    }
                    
                    if(false === $size = filesize($file_path))
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
                    if($exists && !is_writeable($file_path))
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
                    if($is_compressed)
                    {
                        throw new BadFileInfoException($this, "A gzip file cannot be opened for both reading and writting.");
                    }
                    
                    if($exists && !is_readable($file_path))
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
            $handle = \fopen($file_path, $this->mode, $this->use_include, $this->get_stream_context());
        }
        else
        {
            if($is_compressed)
            {
                $handle = \gzopen($file_path, $this->mode, $this->use_include);
            }
            else
            {
                $handle = \fopen($file_path, $this->mode, $this->use_include);
            }
        }
        
        if(false === $handle)
        {
            throw new FileResourceNotAvailableException($this);
        }
        
        return $handle;
    }
}
