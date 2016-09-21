<?php

namespace MKDict\Exporter\SQL;

use MKDict\Exporter\Exporter;

abstract class SQLExporter extends Exporter
{
    /**
     * Constructor
     * 
     * @param int $version_id
     * @param string $type
     */
    public function __construct(int $version_id)
    {
        parent::__construct($version_id, "SQL");
    }
}
