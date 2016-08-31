<?php

namespace MKDict\FileResource;

use MKDict\FileResource\PlainTextFileResource;

//a very specific class for writing special php files which contain only global variables
class PHPFileResource extends PlainTextFileResource
{
    protected $header;
    protected $footer;
    protected $body;
    
    public static function sanatize_string_literal($text)
    {
        return strtr($text, array("\\"=>"\\\\", "\""=>"\\\"", "$"=>"\$"));
    }
    
    public function header($header)
    {
        $this->header = $header;
    }
    
    public function footer($footer)
    {
        $this->footer = $footer;
    }
   
    public function body_add($text)
    {
        $this->body[] = $text;
    }
    
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
