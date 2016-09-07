<?php

define('MANAKYUN', true);

require_once __DIR__ . '/autoload.php';

$options = new MKDict\Command\CommandArgs($argv);
unset($argv);

require_once __DIR__ . '/config.php';
include_once __DIR__ . '/config_dist.php';

mb_internal_encoding('utf8');
set_time_limit(0);

if(!empty(ob_get_status()))
{
    ob_end_clean();
}

$export_version = $options['export_version'];

$export_class_name = "MKDict\\v{$export_version}\\Exporter\\V{$export_version}Exporter";

$exporter = new $export_class_name($export_version);

$exporter->export();

echo "0";