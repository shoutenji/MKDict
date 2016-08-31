<?php

namespace MKDict\v1\Database;

use MKDict\v1\Database\JMDictElement;
use MKDict\v1\Database\Comparable;

class JMDictStringElement extends JMDictElement implements Comparable
{
    public $binary_raw;
    public $binary_nfc, $binary_nfkc, $binary_nfd, $binary_nfkd, $binary_nfd_casefolded, $binary_nfkd_casefolded;
    public $infos = array();
    public $pris = array();
    
    public function __construct(string $binary = "")
    {
        $this->binary_raw = $binary;
        parent::__construct();
    }
    
    public function __toString()
    {
        return $this->binary_raw;
    }
    
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
    
    public function is_equal_to(Comparable $other)
    {
        return self::is_equal($this, $other);
    }
}