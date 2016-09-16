<?php

namespace MKDict\v1\Exporter\SQLite;

use MKDict\Exporter\SQLite;

/**
 * SQL Exporter for version 1
 * 
 * @author Taylor B <taylorbrontario@riseup.net>
 */
class V1SQLExporter extends SQLExporter
{
    /**
     * Constructor
     * 
     * @param int $version_id
     */
    public function __construct(int $version_id)
    {
        global $options, $config;
        
        $this->version_id = $version_id;
        $this->paging_start = 0;
                
        $this->db_conn = new DBConnection($config['dsn'], $config['db_user'], $config['db_pass']);
        $this->jmdb = new JMDictDB($this->db_conn, $this->version_id);
        $this->file = new ByteStreamFileResource(new FileInfo("export_$this->version_id.xml", $config['export_dir'], null, array(), "w"));
        $this->file->open();
    }
    
    public function export()
    {
        
    }
}
