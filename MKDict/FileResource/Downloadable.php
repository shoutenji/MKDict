<?php

namespace MKDict\FileResource;

/**
 * For local files that must be downloaded from a remote source
 * 
 * @author Taylor B <taylorbrontario@riseup.net>
 */
interface Downloadable
{
    /**
     * Downloads data from a remote file and writes to to this file.
     * 
     * @param FileResource $file A file resource encapsulating a remote file from which to download
     * 
     * @return void
     */
    public function download_from(FileResource $file);
}
