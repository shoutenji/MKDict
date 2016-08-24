<?php

define('MANAKYUN', true);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/autoload.php';

use MKDict\Command\CommandArgs;
$options = new CommandArgs($argv);

use MKDict\Logger\InstallLogger;
$logger = new InstallLogger();

use MKDict\Installer\Installer;
$installer = new Installer($options);
$installer->install();

echo "success\n";
    
    /*
    require_once __DIR__ . '/common.php';
    
    require_once JMDICT_DIR . "/loggers/InitLogger.php";
    $logger = new InitLogger();
    $mk_exceptions->register_logger($logger);
    
    if(defined('GENERATE_UTF_DATA'))
    {
        require_once UNICODE_DIR . '/generate_utf_data.php';
    }
    
    if(defined('UTF_TESTS'))
    {
        require UNICODE_DIR . '/utf_tests.php';
    }
    
    if(defined('TEST_DB'))
    {
        $table = new mk_PDO_table($pdo, TEST_TABLE_NAME);
        $table->add_column("t_timestamp", "TIMESTAMP", "", "NOT NULL", "", "");
        $table->add_column("t_varchar", "VARCHAR(255)", "DEFAULT ''", "NULL", "", "");
        $table->add_column("t_text", "TEXT", "", "NULL", "", "");
        $table->add_column("t_int", "INTEGER", "", "NOT NULL", "PRIMARY KEY", "AUTO_INCREMENT");
        $table->create();
        
        $pdo->exec("INSERT INTO ".TEST_TABLE_NAME." VALUES (NULL, '日本語', 'hello world', 234);");
        $pdo->query("SELECT * FROM ".TEST_TABLE_NAME.";");
        $result = $pdo->fetch(PDO::FETCH_ASSOC);
        
        if(!is_array($result))
        {
            mk_db_error("DB test failed!");
        }
    }
    
    $start = time();
    if(defined('CREATE_DB'))
    {
        require DB_DIR . "/create_tables.php";
    }
    $finished = time();
    
    if(defined('BO_OUTPUT'))
    {
        $message = "0";
    }
    else
    {
        $message = "Manakyun successfully installed.";
    }
    
    $logger->log_time($finished - $start);
    $logger->flush();
    
    echo $message;
    */