<?php

namespace MKDict\Logger;

use MKDict\Logger\Logger;

class InstallLogger extends Logger
{
    public function flush()
    {
        $now = date("F j, Y, g:i a [e]");
        $logmsg = "";
    }
}
