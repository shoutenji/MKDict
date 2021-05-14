<?php

namespace MKDict\v1\Exporter\SQL;

use MKDict\Exporter\SQL\SQLExporter;

use MKDict\v1\Database\JMDictDB;
use MKDict\v1\Database\JMDictEntry;
use MKDict\v1\Database\JMDictKanjiElement;
use MKDict\v1\Database\JMDictReadingElement;
use MKDict\v1\Database\JMDictSenseElement;

/**
 * SQL Exporter for version 1
 * 
 * @author Taylor B <taylorbrontario@riseup.net>
 */
class V1SQLExporter extends SQLExporter
{
    const TABLE_NAME = "mk_binary_search";
    const KANJI_KEY = 1;
    const READING_KEY = 2;
    const SENSE_KEY = 3;
    
    /**
     * Factory method for creating a reading element.
     * 
     * @return JMDictEntity A new reading element
     */
    protected function new_reading()
    {
        return new JMDictReadingElement();
    }
    
    /**
     * Factory method for creating a sense element.
     * 
     * @return JMDictEntity A new sense element
     */
    protected function new_sense()
    {
        return new JMDictSenseElement();
    }
    
    /**
     * Factory method for creating a kanji element.
     * 
     * @return JMDictEntity A new kanji element
     */
    protected function new_kanji()
    {
        return new JMDictKanjiElement();
    }
    
    /**
     * Factory method for creating a entry element.
     * 
     * @return JMDictEntity A new entry element
     */
    protected function new_entry()
    {
        return new JMDictEntry();
    }
    
     /**
     * Get the DB that matches export version
     * 
     * @return JMDictDBInterface
     */
    protected function get_versioned_db()
    {
        return new JMDictDB($this->db_conn, $this->version_id);
    }
    
    public function export()
    {
        $this->output_header();
        
        $entries = $this->get_entry_generator();
        foreach($entries as $entry)
        {
            $this->output_entry($entry);
        }
    }
    
    protected function output_footer()
    {
        $this->file->write(";");
    }
    
    protected function output_header()
    {
        $statement = "CREATE TABLE ".$this::TABLE_NAME."(
            `binary`      TEXT,
            `uid`         INTEGER,
            `key`         INTEGER
        );\n";
        
        $statement .= "INSERT INTO ".$this::TABLE_NAME." VALUES ";
        
        $this->file->write($statement);
    }
    
    /**
     * Create an SQL insert string for input data
     * 
     * @param string $binary
     * @param int $uid
     * @param string $ekey
     * 
     * @return string
     */
    protected function insert_line(string $binary = "", int $uid = 0, int $ekey = 0)
    {
        return "('$binary', $uid, $ekey),\n";
    }
    
    protected function output_entry($entry)
    {
        $insert_statements = array();
        
        foreach($entry->readings as $reading)
        {
            if(empty($reading))
            {
                die("reading object null:\n".print_r($entry,true));
            }
            
            $insert_statements[] = $this->insert_line(
                        strtr($reading->binary_nfkd_casefolded ? $reading->binary_nfkd_casefolded : $reading->binary_raw, array('\''=>'\'\'')),
                        $reading->reading_uid,
                        self::READING_KEY
            );
        }
        
        foreach($entry->kanjis as $kanji)
        {
            if(empty($reading))
            {
                die("kanji object null:\n".print_r($entry,true));
            }
            $insert_statements[] = $this->insert_line(
                    strtr($kanji->binary_nfkd_casefolded ? $kanji->binary_nfkd_casefolded : $kanji->binary_raw, array('\''=>'\'\'')),
                    $kanji->kanji_uid,
                    self::KANJI_KEY
            );
        }
        
        foreach($entry->senses as $sense)
        {
            if(!empty($sense->glosses))
            {
                $gloss = $sense->glosses[0];
                if(!isset($gloss['binary_raw']))
                {
                    die("reading object null:\n".print_r($entry,true));
                }
                $insert_statements[] = $this->insert_line(strtr($gloss['binary_raw'], array('\''=>'\'\'')), $sense->sense_uid, self::SENSE_KEY);
            }
        }
        
        array_walk($insert_statements, array($this->file, "write"));
    }
}
