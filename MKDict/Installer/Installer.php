<?php

namespace MKDict\Installer;

use MKDict\Command\CommandArgs;
use MKDict\Logger\InstallLogger;
use MKDict\Database\DB;
use MKDict\FileResource\FileInfo;
use MKDict\FileResource\Url;
use MKDict\FileResource\PlainTextFileResource;

class Installer
{
    protected $options;
    protected $logger;
    protected $db;
    
    public function __construct($argv)
    {
        $this->options = new CommandArgs($argv);
        $this->logger = new InstallLogger();
        $this->db = new DB();
    }
    
    public function install()
    {
        if($this->options['generate_utf_data'])
        {
            //$this->download_unicode_data_files();
            $this->generate_utf8_data_files();
        }
    }
    
    protected function download_unicode_data_files()
    {
        global $config;
        
        //download unicode data files
        foreach($config['unicode_data_files'] as $file_tag => $file_info)
        {
            //the remote unicode data file
            $remote_file_info = new FileInfo();
            $remote_file_info->set_url(new Url($file_info['url']));
            $remote_file_info->set_mode("r");
            $remote_file_info->set_stream_context(array('http' => array(
                    'method'    =>  'GET',
                    'follow_location'   =>  0,
                    'timeout'   =>  120,
                )
            ));
            $remote_data_file = new PlainTextFileResource($remote_file_info);
            $remote_data_file->open();
            
            //the local unicode data file (that we are creating)
            $local_file_info = new FileInfo($file_info['name'], $config['data_dir']);
            $local_file_info->set_mode("w");
            $local_data_file = new PlainTextFileResource($local_file_info);
            $local_data_file->open();
            
            //download the remote file into the local file
            $local_data_file->download_from($remote_data_file);
        }
    }
    
    protected function generate_utf8_data_files()
    {
        global $config;
        
        $unicode_data_files = array();
        
        $canonical_decomp_finfo = new FileInfo("canonical_decomp.php", $config['data_dir']);
        $canonical_decomp_finfo->set_mode("w");
        $canonical_decomp_file = new PHPFile($canonical_decomp_finfo);
        $canonical_decomp_file->open();
        $canonical_decomp_file->header("CanonicalDecomposition");
        
        /*
        define('GENERATED_FILE_PRIMARY_COMPOSITES', GENERATED_DIR . '/primary_composites.php');
        define('GENERATED_FILE_CASE', GENERATED_DIR . '/case_mapping_f.php');
        define('GENERATED_FILE_CCC', GENERATED_DIR . '/ccc_class.php');
        define('GENERATED_FILE_COMPATABILITY_DECOMP', GENERATED_DIR . '/compatibility_decomp.php');
        define('GENERATED_FILE_NFC_QUICK_CHECK', GENERATED_DIR . '/nfc_qc.php');
        define('GENERATED_FILE_NFKC_QUICK_CHECK', GENERATED_DIR . '/nfkc_qc.php');
        */
        
        $case_folding_data_finfo = new FileInfo($config['unicode_data_files']['CaseFolding']['name'], $config['data_dir']);
        $case_folding_data_finfo->set_mode("r");
        $case_folding_data_finfo->set_option(FileInfo::OPTION_IGNORE_BLANK_LINES, true);
        $case_folding_data_finfo->set_option(FileInfo::OPTION_COMMENT_CHAR, "#");
        $case_folding_data_finfo->set_option(FileInfo::OPTION_VALUE_DELIMITER, ";");
        $case_folding_data_file = new CSVFile($case_folding_data_finfo);
        $case_folding_data_file->open();
        $case_folding_data_iterator = $case_folding_data_file->getIterator();
    }
}
