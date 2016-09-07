<?php

namespace MKDict\v1\Database;

/**
 * Convenience class for wrapping array like objects
 * 
 * @author Taylor B <taylorbrontario@riseup.net>
 */
class JMDictElementList extends \ArrayObject
{
    /**
     * Constructor
     * 
     * @param array $arg
     */
    public function __construct(array $arg = array())
    {
        if(!empty($arg))
        {
            parent::__construct($arg);
        }
    }
}
