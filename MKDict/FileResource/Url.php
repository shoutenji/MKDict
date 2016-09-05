<?php

namespace MKDict\FileResource;

/**
 * A class wrapping a URL
 * 
 * @author Taylor B <taylorbrontario@riseup.net>
 */
class Url
{
    /** @var string The url value as a string */
    public $url;
    
    /**
     * Constructor
     * 
     * @param string $url A URL
     */
    public function __construct(string $url)
    {
        $this->url = $url;
    }
    
    /**
     * To string
     * 
     * @return string
     */
    public function to_string()
    {
        return $this->url;
    }
}
