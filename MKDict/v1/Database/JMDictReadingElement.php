<?php

namespace MKDict\v1\Database;

use MKDict\v1\Database\JMDictStringElement;
use MKDict\v1\Database\Comparable;

class JMDictReadingElement extends JMDictStringElement implements Comparable
{
    public $b_no_kanji = 0;  //keep this as an int
    public $restrs = array();
    
    public static function is_equal(Comparable $reading1, Comparable $reading2)
    {
        if($reading1->b_no_kanji !== $reading2->b_no_kanji)
        {
            return false;
        }
        
        if(!JMDictElement::compare_element_lists($reading1->restrs, $reading2->restrs, "binary_raw"))
        {
            return false;
        }
        
        return parent::is_equal($reading1, $reading2);
    }
    
    public function is_equal_to(Comparable $other)
    {
        return self::is_equal($this, $other);
    }
}
