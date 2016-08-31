<?php

namespace MKDict\Exception;

use MKDict\Exception\FatalException;

class LibXMLError extends FatalException
{
    protected $error;
    
    public function __construct(\libXMLError $error)
    {
        $this->error = $error;
        parent::__construct();
    }
    
    public function get_message()
    {
        return "\n" . $this->colorize_text(ltrim(strrchr(__CLASS__, '\\'),'\\')) . "\n\libxml error:\n" . print_r($this->error, true) . "\nTrace:\n" . print_r($this->getTrace(),true);
    }
}
