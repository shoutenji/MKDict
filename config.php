<?php

define("INTEGER_ARRAY_MAX_SIZE", PHP_INT_SIZE == 8 ? 1000 : 2000); //8kB
define('USORT_EQUALITY_CONSTANT', PHP_MAJOR_VERSION >= 7 ? 0 : 1);

function main_exception_handler(Throwable $e)
{
    if(is_a($e, "MKDict\\Exception\\FatalException"))
    {
        echo $e->get_message()."\n";
    }
    else
    {
        throw $e;
    }
}
set_exception_handler("main_exception_handler");

$config = array(
    'db_host' => 'localhost',
    'db_name' => 'manakyun',
    'db_user' => "root",
    'db_pass' => '159061br',
    'db_table_prefix' => 'mk_',
);

$config = array_merge($config, array(
    'table_test' => $config['db_table_prefix'].'test',
    'table_entries' => $config['db_table_prefix'].'entries',
    'table_ants_raw' => $config['db_table_prefix'].'ants_raw',
    'table_xrefs_raw' => $config['db_table_prefix'].'xrefs_raw',

    'table_kanjis' => $config['db_table_prefix'].'kanjis',
    'table_kanjis_infos' => $config['db_table_prefix'].'k_infos',
    'table_kanjis_pris' => $config['db_table_prefix'].'k_pris',

    'table_readings' => $config['db_table_prefix'].'readings',
    'table_reading_restrs' => $config['db_table_prefix'].'restrs',
    'table_reading_infos' => $config['db_table_prefix'].'r_infos',
    'table_reading_pris' => $config['db_table_prefix'].'r_pris',

    'table_senses' => $config['db_table_prefix'].'senses',
    'table_glosses' => $config['db_table_prefix'].'glosses',
    'table_poses' => $config['db_table_prefix'].'poses',
    'table_fields' => $config['db_table_prefix'].'fields',
    'table_miscs' => $config['db_table_prefix'].'miscs',
    'table_s_infos' => $config['db_table_prefix'].'s_infos',
    'table_dials' => $config['db_table_prefix'].'dials',
    'table_lsources' => $config['db_table_prefix'].'lsources',
    'table_stagrs' => $config['db_table_prefix'].'stagrs',
    'table_stagks' => $config['db_table_prefix'].'stagks',
    'table_ants' => $config['db_table_prefix'].'ants',
    'table_xrefs' => $config['db_table_prefix'].'xrefs',
));

$config = array_merge($config, array(
    'EDRDG_domain' => 'http://ftp.monash.edu.au',
    'EDRDG_path' => '/pub/nihongo',
    'JMDict_filename' => '/JMdict_e.gz',
    'HTTP_stream_chunk_size' => 4096,
    'max_file_size' => 100e6, //100mb limit on all file sizes
    'GZ_buffer_rw_size' => 2048,
    'DTD_max_strlen' => pow(2,16),
    'parser_stream_read_size' => 4096,
    'merge_buffer_size' => 1024,
    'element_buffer_size' => 2048,
));

/*
$config = array_merge($config, array(
    'filesize_checkpoint' => floor(0.1 * $config['JMDict_max_file_size'] / $config['HTTP_stream_chunk_size']),
));
*/

$config = array_merge($config, array(
    'data_dir' => __DIR__ . '/var/data',
    'tmp_dir' => __DIR__ . '/var/tmp',
    'log_dir' => __DIR__ . '/var/logs',
));

//Unicode 7.0
$config = array_merge($config, array(
    'unicode_data_files' => array(
        'CaseFolding' => array(
            'name' => 'CaseFolding.txt',
            'url' => 'http://www.unicode.org/Public/7.0.0/ucd/CaseFolding.txt'
        ),
        'DerivedNormalizationProps' => array(
            'name' => 'DerivedNormalizationProps.txt',
            'url' => 'http://www.unicode.org/Public/7.0.0/ucd/DerivedNormalizationProps.txt'
        ),
        'HangulSyllableType' => array(
            'name' => 'HangulSyllableType.txt',
            'url' => 'http://www.unicode.org/Public/7.0.0/ucd/HangulSyllableType.txt'
        ),
        'UnicodeData' => array(
            'name' => 'UnicodeData.txt',
            'url' => 'http://www.unicode.org/Public/7.0.0/ucd/UnicodeData.txt'
        ),
    )
));
