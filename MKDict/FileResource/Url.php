<?php

namespace MKDict\FileResource;

class Url
{
    public $url;
    
    public function __construct(string $url)
    {
        $this->url = $url;
    }
    
    public function to_string()
    {
        return $this->url;
    }
}
