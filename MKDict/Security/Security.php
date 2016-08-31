<?php

namespace MKDict\Security;

class Security
{
    public static function weak_random_string($len = 20)
    {
        $str = "";
        while(strlen($str) < $len)
        {
            $str .= sprintf("%X",rand(0,1e10));
        }
        return substr($str, 0, 20);
    }
}
