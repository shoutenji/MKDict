<?php

namespace MKDict\FileResource;

use MKDict\FileResource\PlainTextFileResource;
use MKDict\FileResource\CSVIterator;
use MKDict\FileResource\FileInfo;
use MKDict\FileResource\Exception\FReadFailureException;

class CSVFileResource extends PlainTextFileResource implements \IteratorAggregate
{
    //don't hold the iterator in memory
    //todo since we aren't holding this iterator in memory, shouldn't we make this method static?
    public function getIterator()
    {
        $data = array();
        
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
                else if($skip_comment_line_char !== "" && substr($line_content, 0, 1) === $skip_comment_line_char)
                {
                    unset($contents[$line_number]);
                }
            }
        }
        
        $data = $contents;
        return new CSVIterator($data);
    }
}
