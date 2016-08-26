<?php

define('MANAKYUN', true);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/autoload.php';

$options = new MKDict\Command\CommandArgs($argv);
unset($argv);

$installer = new MKDict\Installer\Installer();
$installer->install();
