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
    
    //todo change this function name to implode_safe
    public static function flatten_array(array $ary, string $type = "int", string $delimiter = ",")
    {
        array_walk($ary, function(&$value, $key) use ($type){
            settype($value, $type);
        });
        
        return implode($delimiter, $ary);
    }
    
    public static function strip_chars_recursive(string $str, array $chrs)
    {
        $str = trim($str);
        
        do
        {
            $strlen_before = strlen($str);
            $str = str_replace($chrs, '', $str);
            $strlen_after = strlen($str);
        }
        while($strlen_before != $strlen_after);
        
        return trim($str);
    }
    
    public static function remove_empty_array_values(&$ary)
    {
        $position = key($ary);

        while(list($key, $value) = each($ary))
        {
            if(empty($value))
            {
                unset($ary[$key]);
            }
        }

        reset($ary);
        while(key($ary) !== $position)
        {
            if(null == next($ary))
            {
                break;
            }
        }
    }
    
    public static function explode_safe($delimeter, $strvalues, $type = "int", $strip_empty_values = true)
    {
        $values = explode($delimeter, $strvalues);
        
        if($strip_empty_values)
        {
            self::remove_empty_array_values($values);
        }
        
        array_walk($values, function(&$value, $key) use ($type){
            settype($value, $type);
        });
        
        return $values;
    }
}
