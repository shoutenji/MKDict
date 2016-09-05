<?php

namespace MKDict\FileResource;

use MKDict\FileResource\FileInfo;

/**
 * Any class implementing this interface can be used analogous to traditional file
 * 
 * @author Taylor B <taylorbrontario@riseup.net>
 * 
 * @todo This should be made abstract and implement the Downloadable interface
 */
interface FileResource
{
    /**
     * Read bytes from underlying file and return as a string
     *
     * @param int $num_bytes The number of bytes to read
     *
     * @return string The bytes read from this file
     */
    public function read(int $num_bytes);
    
    /**
     * Write bytes to underlying file
     *
     * @param string $bytes The bytes to write to file
     * 
     * @return void
     */
    public function write(string $text);
    
    /**
     * Prepares the file for IO operations
     * 
     * This function must be called prior to any reading/writing
     *
     * @return void
     */
    public function open();
    
    /**
     * Closes the underlying file resource
     * 
     * @return bool True if the underlying file handle is empty, else true on success or false on failure
     * 
     */
    public function close();
    
    
    /**
     * Tests if the file pointer is at the end of the file.
     * 
     * @return bool True if underlying file handle is empty else true if the file pointer is at EOF or an error occurs (including socket timeout) and false otherwise 
     * 
     */
    public function feof();
    
    /**
     * Sets the underlying file's pointer. Note: if the file is a GZFileResource and in read only mode, then then only forward seeks are supported.
     *
     * @param int $offset The byte index offset
     * @param int $whence The whence value is one of the following:
     * 
     * @return void
     */
    public function seek($offset, $whence);
    
    /**
     * Sets the underlying FileInfo object
     * 
     * @param FileInfo $file A file info object to underpin this file resource
     * 
     * @return void
     */
    public function set_finfo(FileInfo $finfo);
    
    /**
     * Get the underlying FileInfo object
     * 
     * @return FileInfo
     */
    public function get_finfo();
    
    /**
     * Resets the file pointer back to the beginning of the file
     * 
     * @return bool True if the underlying file handle is empty, else true on success or false on failure
     */
    public function rewind();
}
