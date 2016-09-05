<?php

namespace MKDict\FileResource;

/**
 * A class that holds CSV data
 * 
 * @todo we should have multiple iterators that back the same array. the point is that each iterator knows what it wants from the data array
 * 
 * @author Taylor B <taylorbrontario@riseup.net>
 */
class CSVIterator extends \ArrayIterator
{
    /**
     * Constructor
     * 
     * @param array $data The underlying CSV data as an array
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
