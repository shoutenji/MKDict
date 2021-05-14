<?php

namespace MKDict\Exporter;

use MKDict\Database\DBConnection;
use MKDict\Database\JMDictEntity;
use MKDict\Database\JMDictDBInterface;
use MKDict\FileResource\FileInfo;
use MKDict\FileResource\ByteStreamFileResource;
use MKDict\Security\Security;


abstract class Exporter
{
    protected $version_id;
    protected $db_conn;
    protected $jmdb;
    protected $file;
    protected $paging_start;
    
    /**
     * The main export function (and probably the only function besides the constructor which need be public)
     * 
     * @return void
     */
    abstract public function export();
    
    /**
     * Get the DB that matches export version
     * 
     * @return JMDictDBInterface
     */
    abstract protected function get_versioned_db();
    
    /**
     * Constructor
     * 
     * @param int $version_id
     * @param string $type
     */
    public function __construct(int $version_id, string $type)
    {
        global $options, $config;
        
        switch(strtoupper($type))
        {
            case "XML":
                $file_suffix = "xml";
                break;
            case "SQL":
                $file_suffix = "sql";
                break;
            default:
                return;
        }
        
        $this->version_id = $version_id;
        $this->paging_start = 0;
        
        $this->db_conn = new DBConnection($config['dsn'], $config['db_user'], $config['db_pass']);
        $this->jmdb = $this->get_versioned_db();
        $this->file = new ByteStreamFileResource(new FileInfo("export_$this->version_id.$file_suffix", $config['export_dir'], null, array(), "w"));
        $this->file->open();
    }
    
    /**
     * Get entries
     * 
     * @return array
     */
    protected function get_entries()
    {
        global $config;
        
        $this->db_conn->prepare("SELECT sequence_id, entry_uid FROM ".$config['table_entries']." WHERE ".$this->db_conn->version_check()." ORDER BY entry_uid LIMIT :paging_start, $config[export_block_size];");
        $this->db_conn->bindValue(":paging_start", $this->paging_start, \PDO::PARAM_INT);
        $this->db_conn->bindValue(":version_removed_id", $this->version_id , \PDO::PARAM_INT);
        $this->db_conn->bindValue(":version_added_id", $this->version_id , \PDO::PARAM_INT);
        $this->db_conn->execute();
        
        $this->paging_start += $config['export_block_size'];
        
        return $this->db_conn->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    /**
     * Generator. Gets the next entry from the db
     * 
     * @return generator(JMDictEntity)
     * 
     * @todo this should be moved to the versioned namespaces
     */
    protected function get_entry_generator()
    {
        while($results = $this->get_entries())
        {
            $entry_uids_flat = Security::flatten_array(array_column($results, "entry_uid"));

            //kanjis
            $kanjis = $this->jmdb->get_kanjis($entry_uids_flat, $this->version_id, \PDO::FETCH_ASSOC | \PDO::FETCH_GROUP, "entry_uid", array("binary_raw", "binary_nfkd_casefold"));

            if(!empty($kanjis))
            {
                $kanji_uids = array();
                foreach($kanjis as $kanji)
                {
                    $kanji_uids = array_merge($kanji_uids, array_column($kanji, "kanji_uid"));
                }
                $kanji_uids_flat = Security::flatten_array($kanji_uids);
                unset($kanji_uids);
            }

            $kanji_pris = $this->jmdb->get_kanji_pris($kanji_uids_flat);
            $kanji_infos = $this->jmdb->get_kanji_infos($kanji_uids_flat);

            //readings
            $readings = $this->jmdb->get_readings($entry_uids_flat, $this->version_id, \PDO::FETCH_ASSOC | \PDO::FETCH_GROUP, "entry_uid", array("binary_raw", "nokanji", "binary_nfkd_casefold"));

            $reading_uids = array();
            foreach($readings as $reading)
            {
                $reading_uids = array_merge($reading_uids, array_column($reading, "reading_uid"));
            }
            $reading_uids_flat = Security::flatten_array($reading_uids);
            unset($reading_uids);

            $reading_pris = $this->jmdb->get_reading_pris($reading_uids_flat);
            $reading_infos = $this->jmdb->get_reading_infos($reading_uids_flat);
            $reading_restrs = $this->jmdb->get_reading_restrs($reading_uids_flat);

            //senses
            $senses = $this->jmdb->get_senses($entry_uids_flat, $this->version_id);

            $sense_uids = array();
            foreach($senses as $sense)
            {
                $sense_uids = array_merge($sense_uids, array_column($sense, "sense_uid"));
            }
            $sense_uids_flat = Security::flatten_array($sense_uids);
            unset($sense_uids);

            $sense_infos = $this->jmdb->get_sense_infos($sense_uids_flat);
            $sense_poses = $this->jmdb->get_sense_poses($sense_uids_flat);
            $sense_fields = $this->jmdb->get_sense_fields($sense_uids_flat);
            $sense_miscs = $this->jmdb->get_sense_miscs($sense_uids_flat);
            $sense_dials = $this->jmdb->get_sense_dials($sense_uids_flat);
            $sense_glosses = $this->jmdb->get_sense_glosses($sense_uids_flat);
            $sense_lsources = $this->jmdb->get_sense_lsources($sense_uids_flat);
            $sense_stagrs = $this->jmdb->get_sense_stagrs($sense_uids_flat);
            $sense_stagks = $this->jmdb->get_sense_stagks($sense_uids_flat);
            $sense_ants = $this->jmdb->get_sense_resolved_ants($sense_uids_flat);
            $sense_xrefs = $this->jmdb->get_sense_resolved_xrefs($sense_uids_flat);

            //loop through the entries, construct each as an object and then output them
            foreach($results as $result)
            {
                $entry = $this->new_entry();
                $entry->entry_uid = $result['entry_uid'];
                $entry->sequence_id = $result['sequence_id'];

                if(isset($kanjis[$entry->entry_uid]))
                {
                    $entry_kanjis = $kanjis[$entry->entry_uid];
                    unset($entry_kanjis[$entry->entry_uid]);

                    foreach($entry_kanjis as $entry_kanji)
                    {
                        $entry_kanji_obj = $this->new_kanji();
                        $entry_kanji_obj->kanji_uid = $entry_kanji['kanji_uid'];
                        $entry_kanji_obj->binary_raw = $entry_kanji['binary_raw'];
                        $entry_kanji_obj->binary_nfkd_casefolded = $entry_kanji['binary_nfkd_casefold'];

                        if(isset($kanji_pris[$entry_kanji_obj->kanji_uid]))
                        {
                            $entry_kanji_obj->pris = $kanji_pris[$entry_kanji_obj->kanji_uid];
                            unset($kanji_pris[$entry_kanji_obj->kanji_uid]);
                        }

                        if(isset($kanji_infos[$entry_kanji_obj->kanji_uid]))
                        {
                            $entry_kanji_obj->infos = $kanji_infos[$entry_kanji_obj->kanji_uid];
                            unset($kanji_infos[$entry_kanji_obj->kanji_uid]);
                        }

                        $entry->kanjis->append($entry_kanji_obj);
                    }
                }

                $entry_readings = $readings[$entry->entry_uid];
                unset($readings[$entry->entry_uid]);

                foreach($entry_readings as $entry_reading)
                {
                    $entry_reading_obj = $this->new_reading();
                    $entry_reading_obj->reading_uid = $entry_reading['reading_uid'];
                    $entry_reading_obj->binary_raw = $entry_reading['binary_raw'];
                    $entry_reading_obj->binary_nfkd_casefolded = $entry_reading['binary_nfkd_casefold'];
                    $entry_reading_obj->b_no_kanji = (INT) $entry_reading['nokanji'];

                    if(isset($reading_restrs[$entry_reading_obj->reading_uid]))
                    {
                        $entry_reading_obj->restrs = $reading_restrs[$entry_reading_obj->reading_uid];
                        unset($reading_restrs[$entry_reading_obj->reading_uid]);
                    }

                    if(isset($reading_pris[$entry_reading_obj->reading_uid]))
                    {
                        $entry_reading_obj->pris = $reading_pris[$entry_reading_obj->reading_uid];
                        unset($reading_pris[$entry_reading_obj->reading_uid]);
                    }

                    if(isset($reading_infos[$entry_reading_obj->reading_uid]))
                    {
                        $entry_reading_obj->infos = $reading_infos[$entry_reading_obj->reading_uid];
                        unset($reading_infos[$entry_reading_obj->reading_uid]);
                    }

                    $entry->readings->append($entry_reading_obj);
                }

                $entry_senses = $senses[$entry->entry_uid];
                unset($senses[$entry->entry_uid]);

                foreach($entry_senses as $entry_sense)
                {
                    $entry_sense_obj = $this->new_sense();
                    $entry_sense_obj->sense_uid = $entry_sense['sense_uid'];

                    if(isset($sense_infos[$entry_sense_obj->sense_uid]))
                    {
                        $entry_sense_obj->infos = $sense_infos[$entry_sense_obj->sense_uid];
                        unset($sense_infos[$entry_sense_obj->sense_uid]);
                    }

                    if(isset($sense_poses[$entry_sense_obj->sense_uid]))
                    {
                        $entry_sense_obj->poses = $sense_poses[$entry_sense_obj->sense_uid];
                        unset($sense_poses[$entry_sense_obj->sense_uid]);
                    }

                    if(isset($sense_fields[$entry_sense_obj->sense_uid]))
                    {
                        $entry_sense_obj->fields = $sense_fields[$entry_sense_obj->sense_uid];
                        unset($sense_fields[$entry_sense_obj->sense_uid]);
                    }

                    if(isset($sense_miscs[$entry_sense_obj->sense_uid]))
                    {
                        $entry_sense_obj->miscs = $sense_miscs[$entry_sense_obj->sense_uid];
                        unset($sense_miscs[$entry_sense_obj->sense_uid]);
                    }

                    if(isset($sense_dials[$entry_sense_obj->sense_uid]))
                    {
                        $entry_sense_obj->dials = $sense_dials[$entry_sense_obj->sense_uid];
                        unset($sense_dials[$entry_sense_obj->sense_uid]);
                    }

                    if(isset($sense_glosses[$entry_sense_obj->sense_uid]))
                    {
                        $entry_sense_obj->glosses = $sense_glosses[$entry_sense_obj->sense_uid];
                        unset($sense_glosses[$entry_sense_obj->sense_uid]);
                    }

                    if(isset($sense_lsources[$entry_sense_obj->sense_uid]))
                    {
                        $entry_sense_obj->lsources = $sense_lsources[$entry_sense_obj->sense_uid];
                        unset($sense_lsources[$entry_sense_obj->sense_uid]);
                    }

                    if(isset($sense_stagrs[$entry_sense_obj->sense_uid]))
                    {
                        $entry_sense_obj->stagrs = $sense_stagrs[$entry_sense_obj->sense_uid];
                        unset($sense_stagrs[$entry_sense_obj->sense_uid]);
                    }

                    if(isset($sense_stagks[$entry_sense_obj->sense_uid]))
                    {
                        $entry_sense_obj->stagks = $sense_stagks[$entry_sense_obj->sense_uid];
                        unset($sense_stagks[$entry_sense_obj->sense_uid]);
                    }

                    if(isset($sense_ants[$entry_sense_obj->sense_uid]))
                    {
                        $entry_sense_obj->ants = $sense_ants[$entry_sense_obj->sense_uid];
                        unset($sense_ants[$entry_sense_obj->sense_uid]);
                    }

                    if(isset($sense_xrefs[$entry_sense_obj->sense_uid]))
                    {
                        $entry_sense_obj->xrefs = $sense_xrefs[$entry_sense_obj->sense_uid];
                        unset($sense_xrefs[$entry_sense_obj->sense_uid]);
                    }

                    $entry->senses->append($entry_sense_obj);
                }

                yield $entry;
            }
        }
    }
}
