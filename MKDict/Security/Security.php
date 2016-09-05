<?php

namespace MKDict\Security;

/**
* A class containing a few static util methods some of which are safe to use in a security context
* 
* @author Taylor B <taylorbrontario@riseup.net>
*/
class Security
{
    /**
     * Generate a non-cryptographically secure random hex string
     * 
     * @param int $len The desired length in bytes
     * 
     * @return string A random hex string
     * 
     * @security not for cryptographic use
     */
    public static function weak_random_string(int $len = 20)
    {
        $str = "";
        while(strlen($str) < $len)
        {
            $str .= sprintf("%X",rand(0,1e10));
        }
        return substr($str, 0, 20);
    }
    
    /**
     * A wrapper for PHP's implode: implodes an array while also type coercing each value
     * 
     * @param array $ary The input data
     * @param string $type The type to convert entries to
     * @param string $delimiter The delimiter to use
     * 
     * @return array the flattened array
     * 
     * @todo change this function name to implode_safe() and test
     */
    public static function flatten_array(array $ary, string $type = "int", string $delimiter = ",")
    {
        $_ary = $ary;
        unset($ary);
        
        array_walk($_ary, function(&$value, $key) use ($type){
            settype($value, $type);
        });
        
        self::remove_empty_array_values($_ary);
        
        return implode($delimiter, $_ary);
    }
    
    
    /**
     * Strip chars recursively
     * 
     * @param string $str The input string
     * @param array $chrs The chars to strip
     * 
     * @return string the input string with specified chars recursively stripped
     */
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
    
    /**
     * Remove empty values from an array
     * 
     * @param array &$ary The input array
     * 
     * @return void
     * 
     * @todo just use array_filter() here
     */
    public static function remove_empty_array_values(array &$ary)
    {
        //save the current position of the array so we can return to it
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
    
     /**
     * A wrapper for PHP's explode: explode an array while remove empty values and type coercing values
     * 
     * @param string $delimeter The delimiter
     * @param string $strvalues The input string
     * @param string $type The type to coerce values to
     * @param bool $strip_empty_values Whether or not to remove empty values
     * 
     * @return array The exploded array
     */
    public static function explode_safe(string $delimeter, string $strvalues, string $type = "int", bool $strip_empty_values = true)
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
