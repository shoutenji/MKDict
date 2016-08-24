<?php

namespace MKDict\FileResource;

use MKDict\FileResource\Downloadable;

class GZFileResource implements FileResource, Downloadable
{
    public function feof()
    {
        return geoz($this->file_handle);
    }
}
