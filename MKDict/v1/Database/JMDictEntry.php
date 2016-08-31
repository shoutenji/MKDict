<?php

namespace MKDict\v1\Database;

use MKDict\v1\Database\JMDictElementList;

class JMDictEntry
{
    public $readings;
    public $kanjis;
    public $senses;

    public $sequence_id = 0;
    public $sense_count = 0;
    public $invalid_data = false;
    public $merge_version_id = 0;

    public function __construct()
    {
        $this->readings = new JMDictElementList();
        $this->kanjis = new JMDictElementList();
        $this->senses = new JMDictElementList();
        parent::__construct();
    }
}
