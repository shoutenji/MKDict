<?php

namespace MKDict\v1\Database;

use MKDict\v1\Database\JMDictStringElement;
use MKDict\v1\Database\Comparable;

class JMDictSenseElement extends JMDictElement implements Comparable
{
    public $glosses = array();
    public $poses = array();
    public $fields = array();
    public $miscs = array();
    public $stagrs = array();
    public $stagks = array();
    public $xrefs = array();
    public $ants = array();
    public $infos = array();
    public $lsources = array();
    public $dials = array();
    public $sense_index = 0;
    
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
    
    public function is_equal_to(Comparable $other)
    {
        return self::is_equal($this, $other);
    }
}