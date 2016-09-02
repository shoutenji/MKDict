<?php

namespace MKDict\v1\Database;

class JMDictElementList extends \ArrayObject
{
    public function __construct(array $arg = array())
    {
        if(!empty($arg))
        {
            parent::__construct($arg);
        }
    }
}
