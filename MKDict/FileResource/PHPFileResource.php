<?php

namespace MKDict\FileResource;

use MKDict\FileResource\ByteStreamFileResource;

/**
 * A file resource class representing a plain text PHP file. Currently only used to write the generated Unicode data files.
 * 
 * @author Taylor B <taylorbrontario@riseup.net>
 */
class PHPFileResource extends ByteStreamFileResource
{
    /** @var string The header text */
    protected $header;
    
    /** @var string The footer text */
    protected $footer;
    
    /** @var string The body text */
    protected $body;
    
    /**
     * Sanatize a string intended to be written to the PHP file as a literal
     * 
     * @param string $text The string to sanatize
     * @return string
     */
    public static function sanatize_string_literal(string $text)
    {
        return strtr($text, array("\\"=>"\\\\", "\""=>"\\\"", "$"=>"\$"));
    }
    
    /**
     * Set header
     * 
     * @param string $header
     * @return void
     */
    public function header(string $header)
    {
        $this->header = $header;
    }
    
    /**
     * Set footer
     * 
     * @param string $footer
     * @return void
     */
    public function footer(string $footer)
    {
        $this->footer = $footer;
    }
   
    /**
     * Add body statement
     * 
     * @param string $text
     * @return void
     */
    public function body_add(string $text)
    {
        $this->body[] = $text;
    }
    
    /**
     * Finally write this file
     * 
     * @return void
     */
    public function create_file()
    {
        $this->write($this->header);
        foreach($this->body as $stmt)
        {
            $this->write($stmt);
        }
        $this->write($this->footer);
    }
}
