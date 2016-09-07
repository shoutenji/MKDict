<?php

namespace MKDict\FileResource;

use MKDict\FileResource\ByteStreamFileResource;
use MKDict\FileResource\CSVIterator;
use MKDict\FileResource\FileInfo;
use MKDict\FileResource\Exception\FReadFailureException;

/**
 * A file resource class representing a CSV file. Some options for this file are set in the FileInfo class.
 * 
 * @author Taylor B <taylorbrontario@riseup.net>
 */
class CSVFileResource extends ByteStreamFileResource implements \IteratorAggregate
{
    /**
     * Get the iterator
     * 
     * @return CSVIterator The iterator object
     * @throws FReadFailureException if the file can't be read
     * 
     * @todo this code should be moved to the CSVIteraor class and take options to act as a filter
     */
    public function getIterator()
    {
        //todo file maybe too large for this operation
        $contents = @file($this->file_info->get_path_name());
        
        if(false === $contents)
        {
            throw new FReadFailureException(debug_backtrace(), $this->file_info);
        }
        
        $skip_whitespace_lines = $this->file_info->get_option(FileInfo::OPTION_IGNORE_BLANK_LINES, true);
        $skip_comment_line_char = $this->file_info->get_option(FileInfo::OPTION_COMMENT_CHAR, "");
        
        if($skip_whitespace_lines || $skip_comment_line_char)
        {
            while(list($line_number, $line_content) = each($contents))
            {
                if($skip_whitespace_lines && strspn($line_content," \t\v\x00\n\r") === strlen($line_content))
                {
                    unset($contents[$line_number]);
                }
                else if($skip_comment_line_char !== "" && $line_content[0] === $skip_comment_line_char)
                {
                    unset($contents[$line_number]);
                }
            }
        }
        
        return new CSVIterator($contents);
    }
}
