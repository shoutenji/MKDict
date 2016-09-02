<?php

namespace MKDict\v1\Database;

use MKDict\Exception\NonExistantPropertyException;
use MKDict\v1\Database\Comparable;
use MKDict\v1\Database\JMDictElementList;
use MKDict\Database\JMDictEntity;

class JMDictElement implements JMDictEntity
{
    public $is_new = true; //helper var
    
    public function __construct() {}

    //Useful for getting null values returned when accessing nonexistant properties without triggering php errors
    public function __get($name)
    {
        global $options;
        
        if($options['debug_version'] && !property_exists($this, $name))
        {
            throw new NonExistantPropertyException("object of class ".get_called_class()." does not have the property $name");
        }
        else
        {
            return null;
        }
    }
    
    public static function compare_element_lists(JMDictElementList $list1, JMDictElementList $list2, ...$keys)
    {
        global $options;
        
        $ary1 = $list1->getArrayCopy();
        $ary2 = $list2->getArrayCopy();
        
        $count1 = count($ary1);
        $count2 = count($ary2);
        
        if($count1 == 0 && $count2 == 0)
        {
            return true;
        }
        if($count1 == 0 ^ $count2 == 0 )
        {
            return false;
        }
        else if($count1 !== $count2)
        {
            return false;
        }
        else if(func_num_args() > 2)
        {
            $keys = array_slice(func_get_args(), 2);
            foreach($keys as $key)
            {
                $this_func = __CLASS__."::".__FUNCTION__;
                if(!$this_func(new JMDictElementList(array_column($ary1, $key)), new JMDictElementList(array_column($ary2, $key))))
                {
                    return false;
                }
            }
        }
        else
        {
            $symetric_difference = array_diff($ary1, $ary2) + array_diff($ary2, $ary1);
            if(!empty($symetric_difference))
            {
                return false;
            }
        }
        
        return true;
    }
}
