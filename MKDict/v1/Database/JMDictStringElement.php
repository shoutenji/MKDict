<?php

namespace MKDict\v1\Database;

use MKDict\v1\Database\JMDictElement;
use MKDict\v1\Database\JMDictElementList;
use MKDict\v1\Database\Comparable;

/**
 * Class for representing a JMDict XML element which contains binary string data
 * 
 * @author Taylor B <taylorbrontario@riseup.net>
 */
class JMDictStringElement extends JMDictElement implements Comparable
{
    public $binary_raw;
    public $binary_nfc, $binary_nfkc, $binary_nfd, $binary_nfkd, $binary_nfd_casefolded, $binary_nfkd_casefolded;
    public $infos;
    public $pris;
    
    /**
     * Constructor
     * 
     * @param string $binary
     */
    public function __construct(string $binary = "")
    {
        $this->binary_raw = $binary;
        $this->infos = new JMDictElementList();
        $this->pris = new JMDictElementList();
        parent::__construct();
    }
    
    /**
     * Magin toString method
     * 
     * @return string
     */
    public function __toString()
    {
        return $this->binary_raw;
    }
    
    /**
     * Test for equality
     * 
     * @param \MKDict\v1\Database\Comparable $instance1
     * @param \MKDict\v1\Database\Comparable $instance2
     * 
     * @return bool True if equal, false otherwise
     */
    public static function is_equal(Comparable $instance1, Comparable $instance2)
    {
        
        if(!empty($instance1->binary_raw) && !empty($instance2->binary_raw))
        {
            if($instance1->binary_raw !== $instance2->binary_raw)
            {
                return false;
            }
        }
        else if(empty($instance1->binary_raw) XOR empty($instance2->binary_raw))
        {
            return false;
        }
        
        if(!JMDictElement::compare_element_lists($instance1->infos, $instance2->infos, "binary_raw"))
        {
            return false;
        }
        
        if(!JMDictElement::compare_element_lists($instance1->pris, $instance2->pris, "binary_raw"))
        {
            return false;
        }
        
        return true;
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