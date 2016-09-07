<?php

namespace MKDict\v1\Database;

use MKDict\v1\Database\JMDictElementList;
use MKDict\v1\Database\JMDictElement;

/**
 * Class for representing a JMDict XML entry element
 * 
 * @author Taylor B <taylorbrontario@riseup.net>
 */
class JMDictEntry extends JMDictElement
{
    public $readings;
    public $kanjis;
    public $senses;

    public $sequence_id;
    public $sense_count;
    public $invalid_data;
    public $merge_version_id;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->readings = new JMDictElementList();
        $this->kanjis = new JMDictElementList();
        $this->senses = new JMDictElementList();
        $this->sequence_id = 0;
        $this->sense_count = 0;
        $this->invalid_data = false;
        $this->merge_version_id = 0;
        parent::__construct();
    }
}
