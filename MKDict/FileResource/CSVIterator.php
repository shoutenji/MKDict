<?php

namespace MKDict\FileResource;

//todo we should have multiple iterators that back the same array. the point is that each iterator knows what it wants from the data array
class CSVIterator extends \ArrayIterator
{
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
