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
            $this->generate_utf_data_files();
        }
    }
    
    protected function download_unicode_data_files()
    {
        global $config;
        
        //download unicode data files
        foreach($config['unicode_files'] as $file_tag => $file_info)
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
            $local_file_info = new FileInfo($file_info['name'], "$config[data_dir]");
            $local_file_info->set_mode("w+");
            $local_data_file = new PlainTextFileResource($local_file_info);
            $local_data_file->open();
            
            //download the remote file into the local file
            $local_data_file->download_from($remote_data_file);
        }
    }
    
    protected function generate_utf_data_files()
    {
        global $config;
        
        
    }
}
