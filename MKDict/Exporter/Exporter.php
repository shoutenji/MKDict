<?php

namespace MKDict\Exporter;

use MKDict\Database\DBConnection;
use MKDict\Database\JMDictEntity;
use MKDict\Security\Security;

/**
 * FatalException
 * 
 * @author Taylor B <taylorbrontario@riseup.net>
 */
abstract class Exporter
{
    protected $version_id;
    protected $db_conn;
    protected $jmdb;
    protected $file;
    protected $exporter;
    
    /**
     * Output header
     */
    public abstract function output_header();
    
    /**
     * Output footer
     */
    public abstract function output_footer();
    
    /**
     * Output entry
     * 
     * @param JMDictEntity $entry
     */
    public abstract function output_entry(JMDictEntity $entry);
    
    /**
     * Get entries
     * 
     * @return array
     */
    public abstract function get_entries();
    
    /**
     * Factory method for creating a reading element.
     * 
     * @return JMDictEntity A new reading element
     */
    public abstract function new_reading();
    
    /**
     * Factory method for creating a sense element.
     * 
     * @return JMDictEntity A new sense element
     */
    public abstract function new_sense();
    
    /**
     * Factory method for creating a kanji element.
     * 
     * @return JMDictEntity A new kanji element
     */
    public abstract function new_kanji();
    
    /**
     * Factory method for creating a entry element.
     * 
     * @return JMDictEntity A new entry element
     */
    public abstract function new_entry();
    
    /**
     * Export db to file
     * 
     * @return void
     */
    public function export()
    {
        $this->output_header();
        while($results = $this->get_entries())
        {
            $entry_uids_flat = Security::flatten_array(array_column($results, "entry_uid"));

            //kanjis
            $kanjis = $this->jmdb->get_kanjis($entry_uids_flat, $this->version_id, \PDO::FETCH_ASSOC | \PDO::FETCH_GROUP, "entry_uid", array("binary_raw", "binary_nfc", "binary_nfd_casefold"));

            $kanji_uids = array();
            foreach($kanjis as $kanji)
            {
                $kanji_uids = array_merge($kanji_uids, array_column($kanji, "kanji_uid"));
            }
            $kanji_uids_flat = Security::flatten_array($kanji_uids);
            unset($kanji_uids);

            $kanji_pris = $this->jmdb->get_kanji_pris($kanji_uids_flat);
            $kanji_infos = $this->jmdb->get_kanji_infos($kanji_uids_flat);

            //readings
            $readings = $this->jmdb->get_readings($entry_uids_flat, $this->version_id, \PDO::FETCH_ASSOC | \PDO::FETCH_GROUP, "entry_uid", array("binary_raw", "nokanji", "binary_nfc", "binary_nfd_casefold"));

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

                $this->output_entry($entry);
            }
        }
        $this->output_footer();
    }
}