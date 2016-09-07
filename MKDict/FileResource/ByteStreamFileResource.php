<?php

namespace MKDict\FileResource;

use MKDict\FileResource\FileInfo;
use MKDict\FileResource\Downloadable;
use MKDict\FileResource\Exception\FileResourceNotAvailableException;
use MKDict\FileResource\Exception\FReadFailureException;
use MKDict\FileResource\Exception\FWriteFailureException;
use MKDict\FileResource\Exception\FileTooLargeException;
use MKDict\FileResource\Exception\FileIOFailureException;

/**
 * A file IO class representing a standard file that is either local or remote where remote refers to one of PHP's built in stream contexts.
 * This class simply wraps PHP's various stream contexts and is underpinned by a FileInfo object. Notably, these read/write functions advance the underlying file pointer.
 *
 * @see http://php.net/manual/en/context.php This class supports all of PHP's various stream contexts
 * @see MKDict\FileResource\FileInfo
 *
 * @todo create a read and write lock
 * @todo the open() function should be called in the constructor 
 * @todo this class should be divided into a LocalFileResource and a RemoteFileResource to make file management easier
 * @todo create a FileNotOpened exception
 * @todo the neccessity of the open() and rewind() functions make the use of this class prone to bugs. try returning a native file handle resource from the open function
 * 
 * @author Taylor B <taylorbrontario@riseup.net>
 */
class ByteStreamFileResource implements FileResource, Downloadable
{
    /** @var FileInfo A file info object to provide information about this file resource */
    protected $file_info;
    
    /** @var resource The native file resource */
    protected $file_handle;
    
    /**
     * Constructor.
     *
     * @param FileInfo $file_info A FileInfo object underpinning this file resource
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
     * OOP wrapper for PHP's fseek()
     * 
     * Sets the underlying file's pointer. Note: if the file is a GZFileResource and in read only mode, then then only forward seeks are supported.
     *
     * @param int $offset The byte index offset
     * @param int $whence The whence value is one of the following:
     *                      SEEK_SET - Set position equal to offset bytes.
     *                      SEEK_CUR - Set position to current location plus offset.
     *                      SEEK_END - Set position to end-of-file plus offset.
     * 
     * @throws FileIOFailureException if the fseek() call fails
     * 
     * @see http://php.net/manual/en/function.fseek.php
     * 
     * @todo throw an error if the case in the above mentioned note is violated
     * 
     * @return void
     */
    public function seek(int $offset, int $whence)
    {
        if(0 > @fseek($this->file_handle, $offset, $whence))
        {
            throw new FileIOFailureException($this->file_info);
        }
    }
    
    /**
     * OOP wrapper for PHP's fread()
     * 
     * Read bytes from underlying file and return as a string
     *
     * @param int $num_bytes The number of bytes to read
     *
     * @throws FReadFailureException if the underlying read operation fails 
     * 
     * @see http://php.net/manual/en/function.fread.php
     * 
     * @return string The bytes read from this file
     */
    public function read(int $num_bytes)
    {
        $bytes = fread($this->file_handle, $num_bytes);
        
        if(false === $bytes)
        {
            throw new FReadFailureException($this->file_info);
        }
        
        return $bytes;
    }
    
    /**
     * OOP wrapper for PHP's fwrite()
     * 
     * Write bytes to underlying file
     *
     * @param string $bytes The bytes to write to file
     * 
     * @throws FWriteFailureException if the underlying write operation fails
     * 
     * @see http://php.net/manual/en/function.fwrite.php
     * 
     * @return void
     */
    public function write(string $bytes)
    {
        $result = @fwrite($this->file_handle, $bytes);
        
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
        $this->file_handle = $this->file_info->get_handle();
    }
    
    /**
     * OOP wrapper for PHP's fgets()
     * 
     * Reads and returns a line from the underlying file.
     * 
     * @see http://php.net/manual/en/function.fgets.php
     *
     * @return string|boolean A line of the file as a string, or false upon failure or if the file has not yet been opened
     * 
     * @todo a regular byte stream should not have this method. move this method to a CSVFile interface or something similar
     */
    public function fgets()
    {
        if(!empty($this->file_handle))
        {
            return @fgets($this->file_handle);
        }
        
        return false;
    }
   
    /**
     * OOP wrapper for PHP's unlink()
     * 
     * Deletes the underlying file
     * 
     * @see http://php.net/manual/en/function.unlink.php
     *
     * @return true on success or false on failure
     * 
     * @todo a regular byte stream should not have this method. move this method to a CSVFile interface or something similar
     */
    public function unlink()
    {
        $this->close($this->file_handle);
        if(!empty($this->file_info))
        {
            return @unlink($this->file_info->get_path_name());
        }
    }
    
    /**
     * OOP wrapper for PHP's feof()
     * 
     * Tests if the file pointer is at the end of the file.
     * 
     * @see http://php.net/manual/en/function.feof.php
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
        
        return @feof($this->file_handle);
    }
    
    /**
     * OOP wrapper for PHP's fclose()
     * 
     * Closes the underlying file resource
     * 
     * @see http://php.net/manual/en/function.fclose.php
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
        
        return @fclose($this->file_handle);
    }
    
    /**
     * OOP wrapper for PHP's rewind()
     * 
     * Resets the file pointer back to the beginning of the file
     * 
     * @see http://php.net/manual/en/function.rewind.php
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
        
        return @rewind($this->file_handle);
    }
}
