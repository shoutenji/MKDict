<?php

namespace MKDict\v1\Database;

use MKDict\Exception\NonExistantPropertyException;
use MKDict\v1\Database\Comparable;
use MKDict\v1\Database\JMDictElementList;
use MKDict\Database\JMDictEntity;

/**
 * An JMDict XML entity
 * 
 * @author Taylor B <taylorbrontario@riseup.net>
 */
class JMDictElement implements JMDictEntity
{
    /** @var bool simple helper var */
    public $is_new = true;
    
    /**
     * Constructor
     */
    public function __construct() {}

    /**
     * Magic __get() method. Useful for returning null when accessing nonexistant properties without triggering php errors
     * 
     * @param string $name
     * @return null
     * 
     * @throws NonExistantPropertyException
     */
    public function __get(string $name)
    {
        global $options;
        
        if($options['debug_version'] && !property_exists($this, $name))
        {
            throw new NonExistantPropertyException("object of class ".get_called_class()." does not have the property $name");
        }
        
        return null;
    }
    
    /**
     * Compare two JMDictElementList objects for equality. This function may call itself one or more times.
     * 
     * @param JMDictElementList $list1
     * @param JMDictElementList $list2
     * 
     * @return bool True if element lists are equal, false otherwise
     * 
     * @todo this function should be moved to the JMDictElementList class
     */
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
                $this_func = __CLASS__."::".__FUNCTION__; //can be replaced by __METHOD__
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
