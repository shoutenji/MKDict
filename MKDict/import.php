<?php

define('MANAKYUN', true);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/autoload.php';

$options = new CommandArgs($argv);

//$importer = new Importer($options);
//$importer->import();