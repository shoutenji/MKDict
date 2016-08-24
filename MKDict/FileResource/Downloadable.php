<?php

namespace MKDict\FileResource;

interface Downloadable
{
    public function download_from(FileResource $file);
}
