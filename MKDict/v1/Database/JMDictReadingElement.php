<?php

namespace MKDict\v1\Database;

use MKDict\v1\Database\JMDictStringElement;
use MKDict\v1\Database\JMDictElementList;
use MKDict\v1\Database\Comparable;

/**
 * Class for representing a JMDict XML reading element
 * 
 * @author Taylor B <taylorbrontario@riseup.net>
 */
class JMDictReadingElement extends JMDictStringElement implements Comparable
{
    public $b_no_kanji;
    public $restrs;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->b_no_kanji = 0;  /** @todo */
        $this->restrs = new JMDictElementList();
        parent::__construct();
    }
    
    /**
     * Test for equality
     * 
     * @param \MKDict\v1\Database\Comparable $reading1
     * @param \MKDict\v1\Database\Comparable $reading2
     * 
     * @return bool True if equal, false otherwise
     */
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
    
    /**
     * Test for equality
     * 
     * @param \MKDict\v1\Database\Comparable $other
     * 
     * @return bool True if equal, false otherwise
     */
    public function is_equal_to(Comparable $other)
    {
        return self::is_equal($this, $other);
    }
}
