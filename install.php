<?php

define('MANAKYUN', true);

require_once __DIR__ . '/autoload.php';

$options = new MKDict\Command\CommandArgs($argv);
unset($argv);

require_once __DIR__ . '/config.php';

mb_internal_encoding('utf8');
set_time_limit(0);

if(!empty(ob_get_status()))
{
    ob_end_clean();
}

$installer = new MKDict\Installer\Installer();
$installer->install();
