<?php

namespace MKDict\v1\Database;

use MKDict\v1\Database\JMDictElementList;
use MKDict\v1\Database\Comparable;

/**
 * Class for representing a JMDict XML sense element
 * 
 * @author Taylor B <taylorbrontario@riseup.net>
 */
class JMDictSenseElement extends JMDictElement implements Comparable
{
    public $glosses;
    public $poses;
    public $fields;
    public $miscs;
    public $stagrs;
    public $stagks;
    public $xrefs;
    public $ants;
    public $infos;
    public $lsources;
    public $dials;
    public $sense_index;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->restrs = new JMDictElementList();
        $this->glosses = new JMDictElementList();
        $this->poses = new JMDictElementList();
        $this->fields = new JMDictElementList();
        $this->miscs = new JMDictElementList();
        $this->stagrs = new JMDictElementList();
        $this->stagks = new JMDictElementList();
        $this->xrefs = new JMDictElementList();
        $this->ants = new JMDictElementList();
        $this->infos = new JMDictElementList();
        $this->lsources = new JMDictElementList();
        $this->dials = new JMDictElementList();
        $this->sense_index = 0;
        parent::__construct();
    }
    
    /**
     * Test for equality
     * 
     * @param \MKDict\v1\Database\Comparable $sense1
     * @param \MKDict\v1\Database\Comparable $sense2
     * 
     * @return bool True if equal, false otherwise
     */
    public static function is_equal(Comparable $sense1, Comparable $sense2)
    {
        if(!JMDictElement::compare_element_lists($sense1->glosses, $sense2->glosses, "binary_raw", "lang", "gend"))
        {
            return false;
        }
        
        if(!JMDictElement::compare_element_lists($sense1->poses, $sense2->poses, "binary_raw"))
        {
            return false;
        }
        
        if(!JMDictElement::compare_element_lists($sense1->fields, $sense2->fields, "binary_raw"))
        {
            return false;
        }
        
        if(!JMDictElement::compare_element_lists($sense1->miscs, $sense2->miscs, "binary_raw"))
        {
            return false;
        }
        
        if(!JMDictElement::compare_element_lists($sense1->stagrs, $sense2->stagrs, "binary_raw"))
        {
            return false;
        }
        
        if(!JMDictElement::compare_element_lists($sense1->stagks, $sense2->stagks, "binary_raw"))
        {
            return false;
        }
        
        if(!JMDictElement::compare_element_lists($sense1->stagks, $sense2->stagks, "binary_raw"))
        {
            return false;
        }
        
        if(!JMDictElement::compare_element_lists($sense1->xrefs, $sense2->xrefs, "binary_raw"))
        {
            return false;
        }
        
        if(!JMDictElement::compare_element_lists($sense1->ants, $sense2->ants, "binary_raw"))
        {
            return false;
        }
        
        if(!JMDictElement::compare_element_lists($sense1->lsources, $sense2->lsources, "binary_raw", "lang", "type", "wasei"))
        {
            return false;
        }
        
        if(!JMDictElement::compare_element_lists($sense1->dials, $sense2->dials, "binary_raw"))
        {
            return false;
        }
        
        if(!JMDictElement::compare_element_lists($sense1->infos, $sense2->infos, "binary_raw"))
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