<?php

namespace MKDict\FileResource;

use MKDict\FileResource\FileResource;
use MKDict\FileResource\Downloadable;
use MKDict\FileResource\Exception\FReadFailureException;
use MKDict\FileResource\Exception\FWriteFailureException;
use MKDict\FileResource\Exception\FileTooLargeException;
use MKDict\FileResource\Exception\FileIOFailureException;

/**
 * A file IO class representing a compressed file
 * 
 * @see MKDict\FileResource\FileResource
 *
 * @author Taylor B <taylorbrontario@riseup.net>
 */
class GZFileResource implements FileResource, Downloadable
{
    /** @var FileInfo The FileInfo object*/
    public $file_info;
    
    /** @var resource A reference to the native file resource */
    protected $file_handle;
    
    /**
     * Constructor
     * 
     * @param \MKDict\FileResource\FileInfo $file_info
     */
    public function __construct(FileInfo $file_info)
    {
        $this->file_info = $file_info;
    }
    
    /**
     * Downloads data from a remote file and writes to to this file.
     * 
     * Call this function to download bytes from a remote file into this file.
     *
     * @param FileResource $file A file resource encapsulating a remote file from which to download
     * 
     * @todo verify this file is a local file and passed file argument is a local file (do not support local copying if passed file argument is also a local file)
     * 
     * @return void
     */
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
    
    /**
     * Sets the underlying FileInfo object
     * 
     * @param FileInfo $file A file info object to underpin this file resource
     * 
     * @return void
     */
    public function set_finfo(FileInfo $finfo)
    {
        $this->file_info = $finfo;
    }
    
    /**
     * Get the underlying FileInfo object
     * 
     * @return FileInfo
     */
    public function get_finfo()
    {
        return $this->file_info;
    }
    
    /**
     * OOP wrapper for PHP's gzseek()
     * 
     * Sets the underlying file's pointer. Note: if the file is a GZFileResource and in read only mode, then then only forward seeks are supported.
     *
     * @param int $offset The byte index offset
     * @param int $whence The whence value is one of the following:
     *                      SEEK_SET - Set position equal to offset bytes.
     *                      SEEK_CUR - Set position to current location plus offset.
     *                      SEEK_END - Set position to end-of-file plus offset.
     * 
     * @throws FileIOFailureException if the gzseek() call fails
     * 
     * @see http://php.net/manual/en/function.gzseek.php
     * 
     * @todo throw an error if the case in the above mentioned note is violated
     * 
     * @return void
     */
    public function seek(int $offset, int $whence)
    {
        if(0 > gzseek($this->file_handle, $offset, $whence))
        {
            throw new FileIOFailureException($this->file_info);
        }
    }
    
    /**
     * OOP wrapper for PHP's gzread()
     * 
     * Read bytes from underlying file and return as a string
     *
     * @param int $num_bytes The number of bytes to read
     *
     * @throws FReadFailureException if the underlying read operation fails 
     * 
     * @see http://php.net/manual/en/function.gzopen.php
     * 
     * @return string The bytes read from this file
     */
    public function read(int $num_bytes)
    {
        $result = gzread($this->file_handle, $num_bytes);
        
        if(false === $result)
        {
            throw new FReadFailureException($this->file_info);
        }
        
        return $result;
    }
    
    /**
     * OOP wrapper for PHP's gzwrite()
     * 
     * Write bytes to underlying file
     *
     * @param string $bytes The bytes to write to file
     * 
     * @throws FWriteFailureException if the underlying write operation fails
     * 
     * @see http://php.net/manual/en/function.gzread.php
     * 
     * @return void
     */
    public function write(string $bytes)
    {
        $result = gzwrite($this->file_handle, $bytes);
        
        if(false === $result)
        {
            throw new FWriteFailureException($this->file_info);
        }
    }
    
    /**
     * Prepares the file for IO operations
     * 
     * This function must be called prior to any reading/writing
     *
     * @return void
     */
    public function open()
    {
        $this->file_handle = $this->file_info->get_handle(true);
    }
    
    /**
     * OOP wrapper for PHP's gzeof()
     * 
     * Tests if the file pointer is at the end of the file.
     * 
     * @see http://php.net/manual/en/function.gzeof.php
     *
     * @return bool True if underlying file handle is empty else true if the file pointer is at EOF or an error occurs (including socket timeout) and false otherwise 
     * 
     */
    public function feof()
    {
        if(empty($this->file_handle))
        {
            return true;
        }
        
        return gzeof($this->file_handle);
    }
    
    /**
     * OOP wrapper for PHP's fclose()
     * 
     * Closes the underlying file resource
     * 
     * @see http://php.net/manual/en/function.gzclose.php
     *
     * @return bool True if the underlying file handle is empty, else true on success or false on failure
     * 
     */
    public function close()
    {
        if(empty($this->file_handle))
        {
            return true;
        }
        
        return gzclose($this->file_handle);
    }
    
    /**
     * OOP wrapper for PHP's gzrewind()
     * 
     * Resets the file pointer back to the beginning of the file
     * 
     * @see http://php.net/manual/en/function.gzrewind.php
     *
     * @return bool True if the underlying file handle is empty, else true on success or false on failure
     * 
     */
    public function rewind()
    {
        if(empty($this->file_handle))
        {
            return false;
        }
        
        return gzrewind($this->file_handle);
    }
}
