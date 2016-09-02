<?php

namespace MKDict\v1\Database;

use MKDict\Database\JMDictDBInterface;
use MKDict\Database\DBConnection;
use MKDict\Database\DBError;
use MKDict\Logger\Logger;
use MKDict\Security\Security;

//todo make all version_id args optional, use $this->version id by default
//tood this class shouldn't do any logging
class JMDictDB implements JMDictDBInterface
{
    protected $db_conn;
    protected $version_id;
    protected $uid_counter;
    
    //the data buffers
    protected $entry_buffer;
    protected $kanji_buffer;
    protected $kanji_info_buffer;
    protected $kanji_pri_buffer;
    protected $reading_buffer;
    protected $sense_buffer;
    protected $reading_info_buffer;
    protected $reading_pri_buffer;
    protected $reading_restr_buffer;
    protected $gloss_buffer;
    protected $dial_buffer;
    protected $pos_buffer;
    protected $field_buffer;
    protected $misc_buffer;
    protected $sense_info_buffer;
    protected $sense_lsources_buffer;
    protected $sense_ants_buffer;
    protected $sense_xrefs_buffer;
    protected $stagrs_buffer;
    protected $stagks_buffer;
    
    public function __construct(DBConnection $db_conn, int $version_id, Logger $logger = null)
    {
        global $config;
        
        $this->db_conn = $db_conn;
        $this->version_id = $version_id;
        $this->logger = $logger;
        $this->uid_counter = 0;
        
        $this->entry_buffer = array();
        $this->kanji_buffer = array();
        $this->kanji_info_buffer = array();
        $this->kanji_pri_buffer = array();
        $this->reading_buffer = array();
        $this->sense_buffer = array();
        $this->reading_info_buffer = array();
        $this->reading_pri_buffer = array();
        $this->reading_restr_buffer = array();
        $this->gloss_buffer = array();
        $this->dial_buffer = array();
        $this->pos_buffer = array();
        $this->field_buffer = array();
        $this->misc_buffer = array();
        $this->sense_info_buffer = array();
        $this->sense_lsources_buffer = array();
        $this->sense_ants_buffer = array();
        $this->sense_xrefs_buffer = array();
        $this->stagrs_buffer = array();
        $this->stagks_buffer = array();
        $this->reference_type_buffer = array(
            $config['table_ants'] => array(),
            $config['table_xrefs'] => array()
        );
    }
    
    public function get_entry_uids(string $sequence_ids_flat)
    {
        global $config;
        
        $this->db_conn->prepare("SELECT entry_uid, sequence_id FROM ".$config['table_entries']." WHERE sequence_id IN($sequence_ids_flat) AND ".$this->db_conn->version_check()." ORDER BY entry_uid;");
        $this->db_conn->bindValue(":version_added_id", $this->version_id, \PDO::PARAM_INT);
        $this->db_conn->bindValue(":version_removed_id", $this->version_id, \PDO::PARAM_INT);
        $this->db_conn->execute();
        return $this->db_conn->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    public function get_diff_entry_uids(string $ids_values_flat)
    {
        global $config;
        
        $this->db_conn->prepare("SELECT sequence_id, entry_uid FROM ".$config['table_entries']." WHERE sequence_id NOT IN($ids_values_flat) AND ".$this->db_conn->version_check().";");
        $this->db_conn->bindValue(":version_removed_id", $this->version_id, \PDO::PARAM_INT);
        $this->db_conn->bindValue(":version_added_id", $this->version_id, \PDO::PARAM_INT);
        $this->db_conn->execute();
        return $this->db_conn->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    public function get_uid($increment = true)
    {
         global $config;
         
        if($this->uid_counter == 0)
        {
            $this->db_conn->query("SELECT key_value, value FROM ".$config['table_meta']." WHERE key_value='uid_counter';");
            $results = $this->db_conn->fetch(\PDO::FETCH_ASSOC);

            if(empty($results))
            {
               return false;
            }

            $this->uid_counter = intval($results['value']);
        }

        if($increment)
        {
            ++$this->uid_counter;
        }

        return $this->uid_counter;
    }

    public function update_uid_counter()
    {
        global $config;

        $value = $this->get_uid(false);
        $this->db_conn->prepare("UPDATE ".$config['table_meta']." SET value=:uid_counter WHERE key_value='uid_counter';");
        $this->db_conn->bindValue(":uid_counter", strval($value), \PDO::PARAM_STR);
        $this->db_conn->execute();
    }

    public function sequence_id_exists($sequence_id)
    {
        global $config;

        $this->db_conn->prepare("SELECT * FROM ".$config['table_entries']." WHERE sequence_id=:sequence_id LIMIT 1;");
        $this->db_conn->bindValue(':sequence_id', $sequence_id, \PDO::PARAM_INT);
        $this->db_conn->execute();

        $result = $this->db_conn->fetch(\PDO::FETCH_ASSOC);
        if(empty($result))
        {
            return false;
        }
        else
        {
            return true;
        }
    }

    public function remove_reading($reading_uid, $version_id)
    {
        global $config;

        $this->db_conn->prepare("UPDATE ".$config['table_readings']." SET version_removed_id=:version_removed_id WHERE reading_uid=:reading_uid;");
        $this->db_conn->bindValue(':reading_uid', $reading_uid, \PDO::PARAM_INT);
        $this->db_conn->bindValue(':version_removed_id', $version_id, \PDO::PARAM_INT);
        $this->db_conn->execute();
    }


    public function remove_sense($sense_uid, $version_id)
    {
        global $config;

        $this->db_conn->prepare("UPDATE ".$config['table_senses']." SET version_removed_id=:version_removed_id WHERE sense_uid=:sense_uid;");
        $this->db_conn->bindValue(':sense_uid', $sense_uid, \PDO::PARAM_INT);
        $this->db_conn->bindValue(':version_removed_id', $version_id, \PDO::PARAM_INT);
        $this->db_conn->execute();
    }


    public function remove_kanji($kanji_uid, $version_id)
    {
        global $config;

        $this->db_conn->prepare("UPDATE ".$config['table_kanjis']." SET version_removed_id=:version_removed_id WHERE kanji_uid=:kanji_uid;");
        $this->db_conn->bindValue(':kanji_uid', $kanji_uid, \PDO::PARAM_INT);
        $this->db_conn->bindValue(':version_removed_id', $version_id, \PDO::PARAM_INT);
        $this->db_conn->execute();
    }


    public function remove_entry($entry_uid, $version_id)
    {
        global $config;

        $this->db_conn->prepare("UPDATE ".$config['table_entries']." SET version_removed_id=:version_removed_id WHERE entry_uid=:entry_uid;");
        $this->db_conn->bindValue(':entry_uid', $entry_uid, \PDO::PARAM_INT);
        $this->db_conn->bindValue(':version_removed_id', $version_id, \PDO::PARAM_INT);
        $this->db_conn->execute();
    }


    public function write_all_buffers($reference_type_only = false)
    {
        global $config;

        if(!$reference_type_only)
        {
            if(!empty($this->entry_buffer))
            {
                $this->write_entries();
                $this->entry_buffer = array();
            }

            if(!empty($this->kanji_buffer))
            {
                $this->write_kanjis();
                $this->kanji_buffer = array();
            }

            if(!empty($this->kanji_info_buffer))
            {
                $this->write_kanji_infos();
                $this->kanji_info_buffer = array();
            }

            if(!empty($this->kanji_pri_buffer))
            {
                $this->write_kanji_pris();
                $this->kanji_pri_buffer = array();
            }

            if(!empty($this->sense_buffer))
            {
                $this->write_senses();
                $this->sense_buffer = array();
            }

            if(!empty($this->reading_buffer))
            {
                $this->write_readings();
                $this->reading_buffer = array();
            }

            if(!empty($this->reading_info_buffer))
            {
                $this->write_reading_infos();
                $this->reading_info_buffer = array();
            }

            if(!empty($this->reading_pri_buffer))
            {
                $this->write_reading_pris();
                $this->reading_pri_buffer = array();
            }

            if(!empty($this->reading_restr_buffer))
            {
                $this->write_restrs();
                $this->reading_restr_buffer = array();
            }

            if(!empty($this->gloss_buffer))
            {
                $this->write_glosses();
                $this->gloss_buffer = array();
            }

            if(!empty($this->dial_buffer))
            {
                $this->write_dials();
                $this->dial_buffer = array();
            }

            if(!empty($this->pos_buffer))
            {
                $this->write_poses();
                $this->pos_buffer = array();
            }

            if(!empty($this->field_buffer))
            {
                $this->write_fields();
                $this->field_buffer = array();
            }

            if(!empty($this->misc_buffer))
            {
                $this->write_miscs();
                $this->misc_buffer = array();
            }

            if(!empty($this->sense_info_buffer))
            {
                $this->write_sense_infos();
                $this->sense_info_buffer = array();
            }

            if(!empty($this->sense_lsources_buffer))
            {
                $this->write_sense_lsources();
                $this->sense_lsources_buffer = array();
            }

            if(!empty($this->sense_ants_buffer))
            {
                $this->write_sense_ants();
                $this->sense_ants_buffer = array();
            }

            if(!empty($this->sense_xrefs_buffer))
            {
                $this->write_sense_xrefs();
                $this->sense_xrefs_buffer = array();
            }

            if(!empty($this->stagrs_buffer))
            {
                $this->write_stagrs();
                $this->stagrs_buffer = array();
            }

            if(!empty($this->stagks_buffer))
            {
                $this->write_stagks();
                $this->stagks_buffer = array();
            }
        }
        else
        {
            if(!empty($this->reference_type_buffer[$config['table_ants']]))
            {
                $this->write_ants();
                $this->reference_type_buffer[$config['table_ants']] = array();
            }

            if(!empty($this->reference_type_buffer[$config['table_xrefs']]))
            {
                $this->write_xrefs();
                $this->reference_type_buffer[$config['table_xrefs']] = array();
            }
        }
    }

    protected function write_restrs()
    {
        global $config;

        $size = count($this->reading_restr_buffer);
        $values = str_repeat("(?,?), ", $size-1) . "(?,?) ";
        $this->db_conn->prepare("INSERT INTO ".$config['table_reading_restrs']." VALUES $values;");
        $index = 0;
        foreach($this->reading_restr_buffer as $restr)
        {
            $this->db_conn->bindValue(++$index, $restr['reading_uid'], \PDO::PARAM_INT); //reading_uid
            $this->db_conn->bindValue(++$index, $restr['kanji_uid'], \PDO::PARAM_INT); //kanji_uid
        }
        $this->db_conn->execute();
    }

    protected function write_kanjis()
    {
        global $config;

        $size = count($this->kanji_buffer);
        $values = str_repeat("(?,?,?,?,?,?,?,?,?,?,?), ", $size-1) . "(?,?,?,?,?,?,?,?,?,?,?) ";
        $this->db_conn->prepare("INSERT INTO ".$config['table_kanjis']." VALUES $values;");
        $index = 0;
        foreach($this->kanji_buffer as $kanji)
        {
            $this->db_conn->bindValue(++$index, $kanji['kanji_uid'], \PDO::PARAM_INT); //kanji_uid
            $this->db_conn->bindValue(++$index, $kanji['entry_uid'], \PDO::PARAM_INT); //entry_uid
            $this->db_conn->bindValue(++$index, $kanji['version_id'], \PDO::PARAM_INT); //version_added_id
            $this->db_conn->bindValue(++$index, isset($kanji['version_removed_id']) ? $kanji['version_removed_id'] : null, \PDO::PARAM_INT); //version_removed_id
            $this->db_conn->bindValue(++$index, $kanji['binary_raw'], \PDO::PARAM_STR);  //binary_raw
            $this->db_conn->bindValue(++$index, $kanji['binary_nfd'], \PDO::PARAM_STR); //binary_nfd
            $this->db_conn->bindValue(++$index, $kanji['binary_nfkd'], \PDO::PARAM_STR); //binary_nfkd
            $this->db_conn->bindValue(++$index, $kanji['binary_nfc'], \PDO::PARAM_STR); //binary_nfc
            $this->db_conn->bindValue(++$index, $kanji['binary_nfkc'], \PDO::PARAM_STR); //binary_nfkc
            $this->db_conn->bindValue(++$index, $kanji['binary_nfd_casefolded'], \PDO::PARAM_STR); //binary_nfd_casefold
            $this->db_conn->bindValue(++$index, $kanji['binary_nfkd_casefolded'], \PDO::PARAM_STR); //binary_nfkd_casefold
        }
        $this->db_conn->execute();
        $this->kanji_buffer = array();
    }

    protected function write_kanji_infos()
    {
        global $config;

        $size = count($this->kanji_info_buffer);
        $values = str_repeat("(?,?), ", $size-1) . "(?,?) ";
        $this->db_conn->prepare("INSERT INTO ".$config['table_kanjis_infos']." VALUES $values;");
        $index = 0;
        foreach($this->kanji_info_buffer as $info)
        {
            $this->db_conn->bindValue(++$index, $info['kanji_uid'], \PDO::PARAM_INT); //kanji_uid
            $this->db_conn->bindValue(++$index, $info['binary_raw'], \PDO::PARAM_INT); //binary_raw
        }
        $this->db_conn->execute();
        $this->kanji_info_buffer = array();
    }

    protected function write_kanji_pris()
    {
        global $config;

        $size = count($this->kanji_pri_buffer);
        $values = str_repeat("(?,?), ", $size-1) . "(?,?) ";
        $this->db_conn->prepare("INSERT INTO ".$config['table_kanjis_pris']." VALUES $values;");
        $index = 0;
        foreach($this->kanji_pri_buffer as $pri)
        {
            $this->db_conn->bindValue(++$index, $pri['kanji_uid'], \PDO::PARAM_INT); //kanji_uid
            $this->db_conn->bindValue(++$index, $pri['binary_raw'], \PDO::PARAM_INT); //binary_raw
        }
        $this->db_conn->execute();
        $this->kanji_pri_buffer = array();
    }


    public function new_kanji(JMDictKanjiElement $kanji, $version_id)
    {
        global $config;

        $kanji_uid = $this->get_uid();

        $this->kanji_buffer[] = array(
            "kanji_uid" => $kanji_uid,
            "entry_uid" => $kanji->entry_uid,
            "version_id" => $version_id,
            "binary_raw" => $kanji->binary_raw,
            "binary_nfd" => $kanji->binary_nfd,
            "binary_nfkd" => $kanji->binary_nfkd,
            "binary_nfc" => $kanji->binary_nfc,
            "binary_nfkc" => $kanji->binary_nfkc,
            "binary_nfd_casefolded" => $kanji->binary_nfd_casefolded,
            "binary_nfkd_casefolded" => $kanji->binary_nfkd_casefolded,
        );

        if(!empty($kanji->infos))
        {
            foreach($kanji->infos as $info)
            {
                $this->kanji_info_buffer[] = array(
                    "kanji_uid" => $kanji_uid,
                    "binary_raw" => $info['binary_raw']
                );
            }

            if(count($this->kanji_info_buffer) > $config['element_buffer_size'])
            {
                $this->write_kanji_infos();
            }
        }

        if(!empty($kanji->pris))
        {
            foreach($kanji->pris as $pri)
            {
                $this->kanji_pri_buffer[] = array(
                    "kanji_uid" => $kanji_uid,
                    "binary_raw" => $pri['binary_raw']
                );
            }

            if(count($this->kanji_pri_buffer) > $config['element_buffer_size'])
            {
                $this->write_kanji_pris();
            }
        }

        if(count($this->kanji_buffer) > $config['element_buffer_size'])
        {
            $this->write_kanjis();
        }
        return $kanji_uid;
    }

    protected function write_readings()
    {
        global $config;

        $size = count($this->reading_buffer);
        $values = str_repeat("(?,?,?,?,?,?,?,?,?,?,?,?), ", $size-1) . "(?,?,?,?,?,?,?,?,?,?,?,?) ";
        $this->db_conn->prepare("INSERT INTO ".$config['table_readings']." VALUES $values;");
        $index = 0;
        foreach($this->reading_buffer as $reading)
        {
            $this->db_conn->bindValue(++$index, $reading['reading_uid'], \PDO::PARAM_INT); //kanji_uid
            $this->db_conn->bindValue(++$index, $reading['entry_uid'], \PDO::PARAM_INT); //entry_uid
            $this->db_conn->bindValue(++$index, $reading['version_added_id'], \PDO::PARAM_INT); //version_added_id
            $this->db_conn->bindValue(++$index, isset($reading['version_removed_id']) ? $reading['version_removed_id'] : null , \PDO::PARAM_INT); //version_removed_id
            $this->db_conn->bindValue(++$index, $reading['binary_raw'], \PDO::PARAM_STR);  //binary_raw
            $this->db_conn->bindValue(++$index, $reading['binary_nfd'], \PDO::PARAM_STR); //binary_nfd
            $this->db_conn->bindValue(++$index, $reading['binary_nfkd'], \PDO::PARAM_STR); //binary_nfkd
            $this->db_conn->bindValue(++$index, $reading['binary_nfc'], \PDO::PARAM_STR); //binary_nfc
            $this->db_conn->bindValue(++$index, $reading['binary_nfkc'], \PDO::PARAM_STR); //binary_nfkc
            $this->db_conn->bindValue(++$index, $reading['binary_nfd_casefolded'], \PDO::PARAM_STR); //binary_nfd_casefold
            $this->db_conn->bindValue(++$index, $reading['binary_nfkd_casefolded'], \PDO::PARAM_STR); //binary_nfkd_casefold
            $this->db_conn->bindValue(++$index, $reading['no_kanji'], \PDO::PARAM_INT); //no_kanji
        }
        $this->db_conn->execute();
        $this->reading_buffer = array();
    }

    protected function write_reading_infos()
    {
        global $config;

        $size = count($this->reading_info_buffer);
        $values = str_repeat("(?,?), ", $size-1) . "(?,?) ";
        $this->db_conn->prepare("INSERT INTO ".$config['table_reading_infos']." VALUES $values;");
        $index = 0;
        foreach($this->reading_info_buffer as $reading_info)
        {
            $this->db_conn->bindValue(++$index, $reading_info['reading_uid'], \PDO::PARAM_INT); //reading_uid
            $this->db_conn->bindValue(++$index, $reading_info['binary_raw'], \PDO::PARAM_STR); //binary_raw
        }
        $this->db_conn->execute();
        $this->reading_info_buffer = array();
    }

    protected function write_reading_pris()
    {
        global $config;

        $size = count($this->reading_pri_buffer);
        $values = str_repeat("(?,?), ", $size-1) . "(?,?) ";
        $this->db_conn->prepare("INSERT INTO ".$config['table_reading_pris']." VALUES $values;");
        $index = 0;
        foreach($this->reading_pri_buffer as $reading_pri)
        {
            $this->db_conn->bindValue(++$index, $reading_pri['reading_uid'], \PDO::PARAM_INT); //reading_uid
            $this->db_conn->bindValue(++$index, $reading_pri['binary_raw'], \PDO::PARAM_STR); //binary_raw
        }
        $this->db_conn->execute();
        $this->reading_pri_buffer = array();
    }

    public function new_reading(JMDictReadingElement $reading, $version_id)
    {
        global $config;

        $reading_uid = $this->get_uid();

        $this->reading_buffer[] = array(
            "reading_uid" => $reading_uid,
            "entry_uid" => $reading->entry_uid,
            "version_added_id" => $version_id,
            "binary_raw" => $reading->binary_raw,
            "binary_nfd" => $reading->binary_nfd,
            "binary_nfkd" => $reading->binary_nfkd,
            "binary_nfc" => $reading->binary_nfc,
            "binary_nfkc" => $reading->binary_nfkc,
            "binary_nfd_casefolded" => $reading->binary_nfd_casefolded,
            "binary_nfkd_casefolded" => $reading->binary_nfkd_casefolded,
            "no_kanji" => $reading->b_no_kanji,
        );
        if(count($this->reading_buffer) > $config['element_buffer_size'])
        {
            $this->write_readings($version_id);
        }

        if(!empty($reading->infos))
        {
            foreach($reading->infos as $info)
            {
                $this->reading_info_buffer[] = array(
                    "reading_uid" => $reading_uid,
                    "binary_raw" => $info['binary_raw']
                );
            }

            if(count($this->reading_info_buffer) > $config['element_buffer_size'])
            {
                $this->write_reading_infos();
            }
        }

        if(!empty($reading->pris))
        {
            foreach($reading->pris as $pri)
            {
                $this->reading_pri_buffer[] = array(
                    "reading_uid" => $reading_uid,
                    "binary_raw" => $pri['binary_raw']
                );
            }

            if(count($this->reading_pri_buffer) > $config['element_buffer_size'])
            {
                $this->write_reading_pris();
            }
        }

        return $reading_uid;
    }

    public function new_sense(JMDictSenseElement $sense, $version_id)
    {
        global $config;

        $sense_uid = $this->get_uid();

        $this->sense_buffer[] = array(
            "sense_uid" => $sense_uid,
            "entry_uid" => $sense->entry_uid,
            "version_added_id" => $version_id,
            "sense_index" => $sense->sense_index,
        );
        if(count($this->sense_buffer) > $config['element_buffer_size'])
        {
            $this->write_senses();
        }

        if(!empty($sense->glosses))
        {
            foreach($sense->glosses as $gloss)
            {
                $this->gloss_buffer[] = array(
                    "sense_uid" => $sense_uid,
                    "binary_raw" => $gloss['binary_raw'],
                    "lang" => $gloss['lang'],
                    "gend" => $gloss['gend']
                );
            }
            if(count($this->gloss_buffer) > $config['element_buffer_size'])
            {
                $this->write_glosses();
            }
        }

        if(!empty($sense->dials))
        {
            foreach($sense->dials as $dial)
            {
                $this->dial_buffer[] = array(
                    "sense_uid" => $sense_uid,
                    "binary_raw" => $dial['binary_raw'],
                );
            }
            if(count($this->dial_buffer) > $config['element_buffer_size'])
            {
                $this->write_dials();
            }
        }

        if(!empty($sense->poses))
        {
            foreach($sense->poses as $pos)
            {
                $this->pos_buffer[] = array(
                    "sense_uid" => $sense_uid,
                    "binary_raw" => $pos['binary_raw'],
                );
            }
            if(count($this->pos_buffer) > $config['element_buffer_size'])
            {
                $this->write_poses();
            }
        }

        if(!empty($sense->fields))
        {
            foreach($sense->fields as $field)
            {
                $this->field_buffer[] = array(
                    "sense_uid" => $sense_uid,
                    "binary_raw" => $field['binary_raw'],
                );
            }
            if(count($this->field_buffer) > $config['element_buffer_size'])
            {
                $this->write_fields();
            }
        }

        if(!empty($sense->miscs))
        {
            foreach($sense->miscs as $misc)
            {
                $this->misc_buffer[] = array(
                    "sense_uid" => $sense_uid,
                    "binary_raw" => $misc['binary_raw'],
                );
            }
            if(count($this->misc_buffer) > $config['element_buffer_size'])
            {
                $this->write_miscs();
            }
        }

        if(!empty($sense->infos))
        {
            foreach($sense->infos as $info)
            {
                $this->sense_info_buffer[] = array(
                    "sense_uid" => $sense_uid,
                    "binary_raw" => $info['binary_raw'],
                );
            }
            if(count($this->sense_info_buffer) > $config['element_buffer_size'])
            {
                $this->write_sense_infos();
            }
        }

        if(!empty($sense->lsources))
        {
            foreach($sense->lsources as $lsource)
            {
                $this->sense_lsources_buffer[] = array(
                    "sense_uid" => $sense_uid,
                    "binary_raw" => $lsource['binary_raw'],
                    "lang" => $lsource['lang'],
                    "type" => $lsource['type'],
                    "wasei" => strtoupper(substr($lsource['wasei'],0,1)) === "Y" ? "Y" : null,
                );
            }
            if(count($this->sense_lsources_buffer) > $config['element_buffer_size'])
            {
                $this->write_sense_lsources();
            }
        }

        if(!empty($sense->ants))
        {
            foreach($sense->ants as $ant)
            {
                $this->sense_ants_buffer[] = array(
                    "sense_uid" => $sense_uid,
                    "binary_raw" => $ant['binary_raw'],
                );
            }
            if(count($this->sense_ants_buffer) > $config['element_buffer_size'])
            {
                $this->write_sense_ants();
            }
        }

        if(!empty($sense->xrefs))
        {
            foreach($sense->xrefs as $xref)
            {
                $this->sense_xrefs_buffer[] = array(
                    "sense_uid" => $sense_uid,
                    "binary_raw" => $xref['binary_raw'],
                );
            }
            if(count($this->sense_xrefs_buffer) > $config['element_buffer_size'])
            {
                $this->write_sense_xrefs();
            }
        }

        return $sense_uid;
    }

    protected function write_sense_xrefs()
    {
        global $config;

        $size = count($this->sense_xrefs_buffer);
        $values = str_repeat("(?,?), ", $size-1) . "(?,?)";
        $this->db_conn->prepare("INSERT INTO ".$config['table_xrefs_raw']." VALUES $values;");
        $index = 0;
        foreach($this->sense_xrefs_buffer as $xref)
        {
            $this->db_conn->bindValue(++$index, $xref['sense_uid'], \PDO::PARAM_INT); //sense_uid
            $this->db_conn->bindValue(++$index, $xref['binary_raw'], \PDO::PARAM_STR); //binary_raw
        }
        $this->db_conn->execute();
        $this->sense_xrefs_buffer = array();
    }

    protected function write_sense_ants()
    {
        global $config;

        $size = count($this->sense_ants_buffer);
        $values = str_repeat("(?,?), ", $size-1) . "(?,?)";
        $this->db_conn->prepare("INSERT INTO ".$config['table_ants_raw']." VALUES $values;");
        $index = 0;
        foreach($this->sense_ants_buffer as $ants)
        {
            $this->db_conn->bindValue(++$index, $ants['sense_uid'], \PDO::PARAM_INT); //sense_uid
            $this->db_conn->bindValue(++$index, $ants['binary_raw'], \PDO::PARAM_STR); //binary_raw
        }
        $this->db_conn->execute();
        $this->sense_ants_buffer = array();
    }

    protected function write_sense_lsources()
    {
        global $config;

        $size = count($this->sense_lsources_buffer);
        $values = str_repeat("(?,?,?,?,?), ", $size-1) . "(?,?,?,?,?)";
        $this->db_conn->prepare("INSERT INTO ".$config['table_lsources']." VALUES $values;");
        $index = 0;
        foreach($this->sense_lsources_buffer as $lsource)
        {
            $this->db_conn->bindValue(++$index, $lsource['sense_uid'], \PDO::PARAM_INT); //sense_uid
            $this->db_conn->bindValue(++$index, $lsource['binary_raw'], \PDO::PARAM_STR); //binary_raw
            $this->db_conn->bindValue(++$index, $lsource['lang'], \PDO::PARAM_STR); //lang
            $this->db_conn->bindValue(++$index, $lsource['type'], \PDO::PARAM_STR); //type
            $this->db_conn->bindValue(++$index, $lsource['wasei'], \PDO::PARAM_STR); //wasei
        }
        $this->db_conn->execute();
        $this->sense_lsources_buffer = array();
    }

    protected function write_sense_infos()
    {
        global $config;

        $size = count($this->sense_info_buffer);
        $values = str_repeat("(?,?), ", $size-1) . "(?,?)";
        $this->db_conn->prepare("INSERT INTO ".$config['table_sense_infos']." VALUES $values;");
        $index = 0;
        foreach($this->sense_info_buffer as $info)
        {
            $this->db_conn->bindValue(++$index, $info['sense_uid'], \PDO::PARAM_INT); //sense_uid
            $this->db_conn->bindValue(++$index, $info['binary_raw'], \PDO::PARAM_STR); //binary_raw
        }
        $this->db_conn->execute();
        $this->sense_info_buffer = array();
    }

    protected function write_miscs()
    {
        global $config;

        $size = count($this->misc_buffer);
        $values = str_repeat("(?,?), ", $size-1) . "(?,?)";
        $this->db_conn->prepare("INSERT INTO ".$config['table_miscs']." VALUES $values;");
        $index = 0;
        foreach($this->misc_buffer as $misc)
        {
            $this->db_conn->bindValue(++$index, $misc['sense_uid'], \PDO::PARAM_INT); //sense_uid
            $this->db_conn->bindValue(++$index, $misc['binary_raw'], \PDO::PARAM_STR); //binary_raw
        }
        $this->db_conn->execute();
        $this->misc_buffer = array();
    }

    protected function write_fields()
    {
        global $config;

        $size = count($this->field_buffer);
        $values = str_repeat("(?,?), ", $size-1) . "(?,?)";
        $this->db_conn->prepare("INSERT INTO ".$config['table_fields']." VALUES $values;");
        $index = 0;
        foreach($this->field_buffer as $field)
        {
            $this->db_conn->bindValue(++$index, $field['sense_uid'], \PDO::PARAM_INT); //sense_uid
            $this->db_conn->bindValue(++$index, $field['binary_raw'], \PDO::PARAM_STR); //binary_raw
        }
        $this->db_conn->execute();
        $this->field_buffer = array();
    }

    protected function write_poses()
    {
        global $config;

        $size = count($this->pos_buffer);
        $values = str_repeat("(?,?), ", $size-1) . "(?,?)";
        $this->db_conn->prepare("INSERT INTO ".$config['table_poses']." VALUES $values;");
        $index = 0;
        foreach($this->pos_buffer as $pos)
        {
            $this->db_conn->bindValue(++$index, $pos['sense_uid'], \PDO::PARAM_INT); //sense_uid
            $this->db_conn->bindValue(++$index, $pos['binary_raw'], \PDO::PARAM_STR); //binary_raw
        }
        $this->db_conn->execute();
        $this->pos_buffer = array();
    }

    protected function write_dials()
    {
        global $config;

        $size = count($this->dial_buffer);
        $values = str_repeat("(?,?), ", $size-1) . "(?,?)";
        $this->db_conn->prepare("INSERT INTO ".$config['table_dials']." VALUES $values;");
        $index = 0;
        foreach($this->dial_buffer as $dial)
        {
            $this->db_conn->bindValue(++$index, $dial['sense_uid'], \PDO::PARAM_INT); //sense_uid
            $this->db_conn->bindValue(++$index, $dial['binary_raw'], \PDO::PARAM_STR); //binary_raw
        }
        $this->db_conn->execute();
        $this->dial_buffer = array();
    }

    protected function write_glosses()
    {
        global $config;

        $size = count($this->gloss_buffer);
        $values = str_repeat("(?,?,?,?), ", $size-1) . "(?,?,?,?)";
        $this->db_conn->prepare("INSERT INTO ".$config['table_glosses']." VALUES $values;");
        $index = 0;
        foreach($this->gloss_buffer as $gloss)
        {
            $this->db_conn->bindValue(++$index, $gloss['sense_uid'], \PDO::PARAM_INT); //sense_uid
            $this->db_conn->bindValue(++$index, $gloss['binary_raw'], \PDO::PARAM_STR); //binary_raw
            $this->db_conn->bindValue(++$index, isset($gloss['lang']) ? $gloss['lang'] : null, \PDO::PARAM_STR); //lang
            $this->db_conn->bindValue(++$index, isset($gloss['gend']) ? $gloss['gend'] : null, \PDO::PARAM_STR); //lang
        }
        $this->db_conn->execute();
        $this->gloss_buffer = array();
    }

    protected function write_senses()
    {
        global $config;

        $size = count($this->sense_buffer);
        $values = str_repeat("(?,?,?,?,?), ", $size-1) . "(?,?,?,?,?)";
        $this->db_conn->prepare("INSERT INTO ".$config['table_senses']." VALUES $values;");
        $index = 0;
        foreach($this->sense_buffer as $sense)
        {
            $this->db_conn->bindValue(++$index, $sense['sense_uid'], \PDO::PARAM_INT); //sense_uid
            $this->db_conn->bindValue(++$index, $sense['entry_uid'], \PDO::PARAM_INT); //entry_uid
            $this->db_conn->bindValue(++$index, $sense['version_added_id'], \PDO::PARAM_INT); //version_added_id
            $this->db_conn->bindValue(++$index, isset($sense['version_removed_id']) ? $sense['version_removed_id'] : null, \PDO::PARAM_INT); //version_removed_id
            $this->db_conn->bindValue(++$index, $sense['sense_index'], \PDO::PARAM_INT); //sense_index
        }
        $this->db_conn->execute();
        $this->sense_buffer = array();
    }

    protected function write_entries()
    {
        global $config;

        $size = count($this->entry_buffer);
        $values = str_repeat("(?,?,?,?), ", $size-1) . "(?,?,?,?) ";
        $this->db_conn->prepare("INSERT INTO ".$config['table_entries']." VALUES $values;");
        $index = 0;
        foreach($this->entry_buffer as  $entry)
        {
            $this->db_conn->bindValue(++$index, $entry['entry_uid'], \PDO::PARAM_INT); //entry_uid
            $this->db_conn->bindValue(++$index, $entry['sequence_id'], \PDO::PARAM_INT); //sequence_id
            $this->db_conn->bindValue(++$index, $entry['version_added_id'], \PDO::PARAM_INT); //version_added_id
            $this->db_conn->bindValue(++$index, isset($entry['version_removed_id']) ? $entry['version_removed_id'] : null, \PDO::PARAM_INT); //version_removed_id
        }
        $this->db_conn->execute();
        $this->entry_buffer = array();
    }

    public function new_entry(JMDictEntry $entry, $version_id)
    {
        global $config;

        $entry_uid = $this->get_uid();
        $this->entry_buffer[] = array(
            "entry_uid" => $entry_uid,
            "sequence_id" => $entry->sequence_id,
            "version_added_id" => $version_id,
        );
        if(count($this->entry_buffer) > $config['element_buffer_size'])
        {
            $this->write_entries();
        }

        return $entry_uid;
    }


    //this works for non-indexed columns and columns with duplicate values
    private function get_column_vacancy($tablename, $columname)
    {
        if(false === $tablename = $this->clean_tablename($tablename))
        {
            return false;
        }

        if(false === $columname = $this->clean_columnname($tablename, $columname))
        {
            return false;
        }

        $this->db_conn->query("SELECT $columname FROM $tablename ORDER BY $columname LIMIT 1;");
        $results = $this->db_conn->fetchAll(\PDO::FETCH_ASSOC);

        //if there are no rows, or the first row does not start at 1, then row 1 is vacant
        if(empty($results) || $results[0][$columname] > 1)
        {
            return 1;
        }

        //otherwise find the first gap in the sequence of the column's values
        $this->db_conn->query("SELECT T1.$columname + 1 AS id FROM $tablename AS T1 WHERE NOT EXISTS (SELECT NULL FROM $tablename AS T2 WHERE T2.$columname = T1.$columname + 1 ORDER BY T2.$columname) ORDER BY T1.$columname LIMIT 1;");
        $result = $this->db_conn->fetch(\PDO::FETCH_ASSOC);

        if(empty($result))
        {
            return false;
        }

        return $result['id'] === 0 ? false : $result['id'];
    }


    private function clean_tablename($tablename)
    {
        global $config;

        $tablename =  Security::strip_chars_recursive($tablename, array("\n","\t","\v","\0","\r"," ","*","%","'","\"","\\", ";","-","#"));
        $valid = true;

        if(substr($tablename, 0, strlen($config['db_table_prefix'])) !== $config['db_table_prefix'])
        {
            $valid = false;
        }

        $this->db_conn->query("SHOW TABLES LIKE '$tablename'");
        $results = $this->db_conn->fetchAll(\PDO::FETCH_ASSOC);

        if(empty($results))
        {
            $valid = false;
        }
        else if(count($results) > 1)
        {
            $valid = false;
        }

        if($valid)
        {
            return $tablename;
        }
        else
        {
            if($config['debug_version'])
            {
                throw new DBError("Invalid tablename specified: $tablename");
            }
            else
            {
                return false;
            }
        }
    }

    //TODO this function serves no purpose anymore
    private function clean_columnname($tablename, $columnname)
    {
        $valid = true;
        if(false === $tablename = $this->clean_tablename($tablename))
        {
            $valid = false;
        }

        $columnname = Security::strip_chars_recursive($columnname, array("\n","\t","\v","\0","\r"," ","*","%","'","\"","\\", ";","-","#"));

        $this->db_conn->query("SHOW COLUMNS FROM $tablename LIKE '$columnname'");
        $results = $this->db_conn->fetchAll(\PDO::FETCH_ASSOC);

        if(empty($results))
        {
            $valid = false;
        }
        else if(count($results) > 1)
        {
            $valid = false;
        }

        if($valid)
        {
            return $columnname;
        }
        else
        {
            if($config['debug_version'])
            {
                throw new DBError("Invalid tablename specified: $tablename");
            }
            else
            {
                return false;
            }
        }
    }

    public function update_restrs(JMDictReadingElement $reading)
    {
        global $config;

        foreach($reading->restrs as $restr)
        {
            if(isset($restr['kanji_uid']))
            {
                $this->reading_restr_buffer[] = array(
                    'reading_uid' => $reading->reading_uid,
                    'kanji_uid' => $restr['kanji_uid']
                );
            }
        }

        if(count($this->reading_restr_buffer) > $config['element_buffer_size'])
        {
            $this->write_restrs();
            $this->reading_restr_buffer = array();
        }
    }

    public function update_stagrs($stagrs)
    {
        global $config;

        foreach($stagrs as $stagr)
        {
            if(isset($stagr['reading_uid']))
            {
                $this->stagrs_buffer[] = array(
                    'sense_uid' => $stagr['sense_uid'],
                    'reading_uid' => $stagr['reading_uid']
                );
            }
        }

        if(count($this->stagrs_buffer) > $config['element_buffer_size'])
        {
            $this->write_stagrs();
        }
    }

    protected function write_stagrs()
    {
        global $config;

        $size = count($this->stagrs_buffer);
        $values = str_repeat("(?,?), ", $size-1) . "(?,?)";
        $this->db_conn->prepare("INSERT INTO ".$config['table_stagrs']." VALUES $values;");
        $index = 0;
        foreach($this->stagrs_buffer as $stagr)
        {
            $this->db_conn->bindValue(++$index, $stagr['sense_uid'], \PDO::PARAM_INT); //sense_uid
            $this->db_conn->bindValue(++$index, $stagr['reading_uid'], \PDO::PARAM_INT); //reading_uid
        }
        $this->db_conn->execute();
        $this->stagrs_buffer = array();
    }

    public function update_stagks($stagks)
    {
        global $config;

        foreach($stagks as $stagk)
        {
            if(isset($stagk['kanji_uid']))
            {
                $this->stagks_buffer[] = array(
                    'sense_uid' => $stagk['sense_uid'],
                    'kanji_uid' => $stagk['kanji_uid']
                );
            }
        }

        if(count($this->stagks_buffer) > $config['element_buffer_size'])
        {
            $this->write_stagks();
        }
    }

    protected function write_stagks()
    {
        global $config;

        $size = count($this->stagks_buffer);
        $values = str_repeat("(?,?), ", $size-1) . "(?,?)";
        $this->db_conn->prepare("INSERT INTO ".$config['table_stagks']." VALUES $values;");
        $index = 0;
        foreach($this->stagks_buffer as $stagk)
        {
            $this->db_conn->bindValue(++$index, $stagk['sense_uid'], \PDO::PARAM_INT); //sense_uid
            $this->db_conn->bindValue(++$index, $stagk['kanji_uid'], \PDO::PARAM_INT); //kanji_uid
        }
        $this->db_conn->execute();
        $this->stagks_buffer = array();
    }

    public function flush_xrefs()
    {
        global $config;

        $this->db_conn->query("SELECT * FROM ".$config['table_xrefs_raw'].";");
        return $this->db_conn->fetchAll(\PDO::FETCH_ASSOC);
    }


    public function flush_ants()
    {
        global $config;

        $this->db_conn->query("SELECT * FROM ".$config['table_ants_raw'].";");
        return $this->db_conn->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function k_or_r_search($version_id, $stringv, $sense_index = 0)
    {
        global $config;

        $this->db_conn->prepare("
            SELECT 'reading' AS type, A.entry_uid, A.reading_uid AS t_uid, A.binary_raw FROM ".$config['table_readings']." AS A WHERE A.binary_raw=:stringv1 AND ".$this->db_conn->version_check("A")."
            UNION
            SELECT 'kanji' AS type, B.entry_uid, B.kanji_uid AS t_uid, B.binary_raw FROM ".$config['table_kanjis']." AS B WHERE B.binary_raw=:stringv2 AND ".$this->db_conn->version_check("B")."
        ;");
        $this->db_conn->bindValue(":stringv1", $stringv, \PDO::PARAM_STR);
        $this->db_conn->bindValue(":stringv2", $stringv, \PDO::PARAM_STR);
        $this->db_conn->bindValue(":A_version_removed_id", $version_id, \PDO::PARAM_INT);
        $this->db_conn->bindValue(":A_version_added_id", $version_id, \PDO::PARAM_INT);
        $this->db_conn->bindValue(":B_version_removed_id", $version_id, \PDO::PARAM_INT);
        $this->db_conn->bindValue(":B_version_added_id", $version_id, \PDO::PARAM_INT);
        $this->db_conn->execute();
        $pre_results = $this->db_conn->fetchAll(\PDO::FETCH_ASSOC);

        if(empty($pre_results))
        {
            //$this->logger->k_or_r_search_failure($stringv, $sense_index);
            return false;
        }

        $results = array();
        foreach($pre_results as &$result)
        {
            $result["t_$result[type]_uid"] = $result["t_uid"];
            unset($result["t_uid"]);

            if(!empty($sense_index))
            {
                $this->db_conn->prepare("SELECT sense_uid FROM ".$config['table_senses']." WHERE entry_uid=:entry_uid AND sense_index=:sense_index AND ".$this->db_conn->version_check().";");
                $this->db_conn->bindValue(":entry_uid", $result['entry_uid'], \PDO::PARAM_INT);
                $this->db_conn->bindValue(":sense_index", $sense_index, \PDO::PARAM_INT);
                $this->db_conn->bindValue(":version_removed_id", $version_id, \PDO::PARAM_INT);
                $this->db_conn->bindValue(":version_added_id", $version_id, \PDO::PARAM_INT);
                $this->db_conn->execute();

                $senses = $this->db_conn->fetchAll(\PDO::FETCH_ASSOC);
                if(empty($senses))
                {
                    continue;
                }

                foreach($senses as $sense)
                {
                    $result['t_sense_uid'] = $sense['sense_uid'];
                    $results[] = $result;
                }
            }
            else
            {
                $results[] = $result;
            }
        }
        unset($result);

        if(empty($results))
        {
            //$this->logger->k_or_r_search_failure($stringv, $sense_index);
            return false;
        }

        return $results;
    }

    public function k_and_r_search($version_id, $kanji_binary, $reading_binary, $sense_index = 0)
    {
        global $config;

        $this->db_conn->prepare("SELECT R.entry_uid AS entry_uid, R.binary_raw, R.reading_uid AS t_reading_uid, K.binary_raw, K.kanji_uid AS t_kanji_uid FROM ".$config['table_readings']." AS R JOIN ".$config['table_kanjis']." AS K 
             USING(entry_uid) WHERE K.binary_raw=:kanji_binary AND R.binary_raw=:reading_binary  AND ".$this->db_conn->version_check("R")." 
             AND ".$this->db_conn->version_check("K").";"
        );

        $this->db_conn->bindValue(":kanji_binary", $kanji_binary, \PDO::PARAM_STR);
        $this->db_conn->bindValue(":reading_binary", $reading_binary, \PDO::PARAM_STR);
        $this->db_conn->bindValue(":R_version_removed_id", $version_id, \PDO::PARAM_INT);
        $this->db_conn->bindValue(":R_version_added_id", $version_id, \PDO::PARAM_INT);
        $this->db_conn->bindValue(":K_version_removed_id", $version_id, \PDO::PARAM_INT);
        $this->db_conn->bindValue(":K_version_added_id", $version_id, \PDO::PARAM_INT);
        $this->db_conn->execute();
        $pre_results = $this->db_conn->fetchAll(\PDO::FETCH_ASSOC);

        if(empty($pre_results))
        {
            //$this->logger->k_and_r_search_failure($kanji_binary, $reading_binary, $sense_index);
            return false;
        }

        $results = array();
        foreach($pre_results as $result)
        {
            if(!empty($sense_index))
            {
                $this->db_conn->prepare("SELECT entry_uid, sense_uid FROM ".$config['table_senses']." WHERE entry_uid=:entry_uid AND sense_index=:sense_index AND ".$this->db_conn->version_check().";");
                $this->db_conn->bindValue(":entry_uid", $result['entry_uid'], \PDO::PARAM_INT);
                $this->db_conn->bindValue(":sense_index", $sense_index, \PDO::PARAM_INT);
                $this->db_conn->bindValue(":version_removed_id", $version_id, \PDO::PARAM_INT);
                $this->db_conn->bindValue(":version_added_id", $version_id, \PDO::PARAM_INT);
                $this->db_conn->execute();

                $senses = $this->db_conn->fetchAll(\PDO::FETCH_ASSOC);
                if(empty($senses))
                {
                    continue;
                }

                foreach($senses as $sense)
                {
                    $new_result = $result;
                    $new_result['t_sense_uid'] = $sense['sense_uid'];
                    $results[] = $new_result;
                }
            }
            else
            {
                $results[] = $result;
            }
        }

        if(empty($results))
        {
            //$this->logger->k_and_r_search_failure($kanji_binary, $reading_binary, $sense_index);
            return false;
        }

        return $results;
    }

    protected function write_xrefs()
    {
        global $config;

        $size = count($this->reference_type_buffer[$config['table_xrefs']]);
        $values = str_repeat("(?,?,?,?,?), ", $size-1) . "(?,?,?,?,?) ";
        $this->db_conn->prepare("INSERT INTO ".$config['table_xrefs']." VALUES $values;");
        $index = 0;
        foreach($this->reference_type_buffer[$config['table_xrefs']] as $ref)
        {
            $this->db_conn->bindValue(++$index, $ref['sense_uid'], \PDO::PARAM_INT); //sense_uid
            $this->db_conn->bindValue(++$index, $ref['binary_raw'], \PDO::PARAM_STR); //binary_raw
            $this->db_conn->bindValue(++$index, isset($ref['t_kanji_uid']) ? $ref['t_kanji_uid'] : null, \PDO::PARAM_INT); //t_kanji_uid
            $this->db_conn->bindValue(++$index, isset($ref['t_reading_uid']) ? $ref['t_reading_uid'] : null, \PDO::PARAM_INT); //t_reading_uid
            $this->db_conn->bindValue(++$index, isset($ref['t_sense_uid']) ? $ref['t_sense_uid'] : null, \PDO::PARAM_INT); //t_sense_uid
        }
        $this->db_conn->execute();
        $this->reference_type_buffer[$config['table_xrefs']] = array();
    }

    protected function write_ants()
    {
        global $config;

        $size = count($this->reference_type_buffer[$config['table_ants']]);
        $values = str_repeat("(?,?,?,?,?), ", $size-1) . "(?,?,?,?,?) ";
        $this->db_conn->prepare("INSERT INTO ".$config['table_ants']." VALUES $values;");
        $index = 0;
        foreach($this->reference_type_buffer[$config['table_ants']] as $ref)
        {
            $this->db_conn->bindValue(++$index, $ref['sense_uid'], \PDO::PARAM_INT); //sense_uid
            $this->db_conn->bindValue(++$index, $ref['binary_raw'], \PDO::PARAM_STR); //binary_raw
            $this->db_conn->bindValue(++$index, isset($ref['t_kanji_uid']) ? $ref['t_kanji_uid'] : null, \PDO::PARAM_INT); //t_kanji_uid
            $this->db_conn->bindValue(++$index, isset($ref['t_reading_uid']) ? $ref['t_reading_uid'] : null, \PDO::PARAM_INT); //t_reading_uid
            $this->db_conn->bindValue(++$index, isset($ref['t_sense_uid']) ? $ref['t_sense_uid'] : null, \PDO::PARAM_INT); //t_sense_uid
        }
        $this->db_conn->execute();
        $this->reference_type_buffer[$config['table_ants']] = array();
    }

    public function new_reference_types($sense_uid, $results, $insertion_point, $binary_raw)
    {
        global $config;

        if(false === $tablename_clean = $this->clean_tablename($insertion_point))
        {
            return false;
        }

        foreach($results as $result)
        {
            $this->reference_type_buffer[$tablename_clean][] = array(
                "sense_uid" => $sense_uid,
                "binary_raw" => $binary_raw,
                "t_reading_uid" => isset($result['t_reading_uid']) ? $result['t_reading_uid'] : null,
                "t_kanji_uid" => isset($result['t_kanji_uid']) ? $result['t_kanji_uid'] : null,
                "t_sense_uid" => isset($result['t_sense_uid']) ? $result['t_sense_uid'] : null,
            );
        }

        if(count($this->reference_type_buffer[$config['table_ants']]) > $config['element_buffer_size'])
        {
            $this->write_ants();
        }
        if(count($this->reference_type_buffer[$config['table_xrefs']]) > $config['element_buffer_size'])
        {
            $this->write_xrefs();
        }
    }

    public function get_kanjis($entry_uids_flat, $version_id, $fetch_style = \PDO::FETCH_ASSOC, $order_by = "kanji_uid", $fields = array("binary_raw"))
    {
        global $config;

        //TODO SQL injection
        $fields = implode(", ", $fields);
        $this->db_conn->prepare("SELECT entry_uid, kanji_uid, $fields FROM ".$config['table_kanjis']."  WHERE entry_uid IN($entry_uids_flat) AND ".$this->db_conn->version_check()." ORDER BY $order_by;");
        $this->db_conn->bindValue(":version_added_id", $version_id, \PDO::PARAM_INT);
        $this->db_conn->bindValue(":version_removed_id", $version_id, \PDO::PARAM_INT);
        $this->db_conn->execute();
        return $this->db_conn->fetchAll($fetch_style);
    }

    public function get_kanji_pris($kanji_uids_flat)
    {
        global $config;

        $this->db_conn->query("SELECT kanji_uid, binary_raw FROM ".$config['table_kanjis_pris']." WHERE kanji_uid IN($kanji_uids_flat) ORDER BY kanji_uid;");
        return $this->db_conn->fetchAll(\PDO::FETCH_ASSOC | \PDO::FETCH_GROUP);
    }

    public function get_kanji_infos($kanji_uids_flat)
    {
        global $config;

        $this->db_conn->query("SELECT kanji_uid, binary_raw FROM ".$config['table_kanjis_infos']." WHERE kanji_uid IN($kanji_uids_flat) ORDER BY kanji_uid;");
        return $this->db_conn->fetchAll(\PDO::FETCH_ASSOC | \PDO::FETCH_GROUP);
    }

    public function get_readings($entry_uids_flat, $version_id, $fetch_style = \PDO::FETCH_ASSOC, $order_by = "reading_uid", $fields = array("binary_raw"))
    {
        global $config;

        //TODO SQL injection
        $fields = implode(",", $fields);
        $this->db_conn->prepare("SELECT entry_uid, reading_uid, $fields FROM ".$config['table_readings']."  WHERE entry_uid IN($entry_uids_flat) AND ".$this->db_conn->version_check()." ORDER BY $order_by;");
        $this->db_conn->bindValue(":version_added_id", $version_id, \PDO::PARAM_INT);
        $this->db_conn->bindValue(":version_removed_id", $version_id, \PDO::PARAM_INT);
        $this->db_conn->execute();
        return $this->db_conn->fetchAll($fetch_style);
    }

    public function get_reading_pris($reading_uids_flat)
    {
        global $config;

        $this->db_conn->query("SELECT reading_uid, binary_raw FROM ".$config['table_reading_pris']." WHERE reading_uid IN($reading_uids_flat) ORDER BY reading_uid;");
        return $this->db_conn->fetchAll(\PDO::FETCH_ASSOC | \PDO::FETCH_GROUP);
    }

    public function get_reading_infos($reading_uids_flat)
    {
        global $config;

        $this->db_conn->query("SELECT reading_uid, binary_raw FROM ".$config['table_reading_infos']." WHERE reading_uid IN($reading_uids_flat) ORDER BY reading_uid;");
        return $this->db_conn->fetchAll(\PDO::FETCH_ASSOC | \PDO::FETCH_GROUP);
    }

    public function get_reading_restrs($reading_uids_flat)
    {
        global $config;

        $this->db_conn->query("SELECT R.reading_uid, K.kanji_uid, K.binary_raw FROM ".$config['table_reading_restrs']." AS R JOIN ".$config['table_kanjis']." AS K USING(kanji_uid) WHERE reading_uid IN($reading_uids_flat) ORDER BY reading_uid;");
        return $this->db_conn->fetchAll(\PDO::FETCH_ASSOC | \PDO::FETCH_GROUP);
    }

    public function get_senses($entry_uids_flat, $version_id, $fetch_style = \PDO::FETCH_ASSOC | \PDO::FETCH_GROUP, $order_by = "entry_uid")
    {
        global $config;

        $this->db_conn->prepare("SELECT entry_uid, sense_uid FROM ".$config['table_senses']." WHERE entry_uid IN($entry_uids_flat) AND ".$this->db_conn->version_check()." ORDER BY $order_by;");
        $this->db_conn->bindValue(":version_added_id", $version_id, \PDO::PARAM_INT);
        $this->db_conn->bindValue(":version_removed_id", $version_id, \PDO::PARAM_INT);
        $this->db_conn->execute();
        return $this->db_conn->fetchAll($fetch_style);
    }

    public function get_sense_infos($sense_uids_flat)
    {
        global $config;

        $this->db_conn->query("SELECT sense_uid, binary_raw FROM ".$config['table_sense_infos']." WHERE sense_uid IN($sense_uids_flat) ORDER BY sense_uid;");
        return $this->db_conn->fetchAll(\PDO::FETCH_ASSOC | \PDO::FETCH_GROUP);
    }


    public function get_sense_poses($sense_uids_flat)
    {
        global $config;

        $this->db_conn->query("SELECT sense_uid, binary_raw FROM ".$config['table_poses']." WHERE sense_uid IN($sense_uids_flat) ORDER BY sense_uid;");
        return $this->db_conn->fetchAll(\PDO::FETCH_ASSOC | \PDO::FETCH_GROUP);
    }


    public function get_sense_fields($sense_uids_flat)
    {
        global $config;

        $this->db_conn->query("SELECT sense_uid, binary_raw FROM ".$config['table_fields']." WHERE sense_uid IN($sense_uids_flat) ORDER BY sense_uid;");
        return $this->db_conn->fetchAll(\PDO::FETCH_ASSOC | \PDO::FETCH_GROUP);
    }

    public function get_sense_miscs($sense_uids_flat)
    {
        global $config;

        $this->db_conn->query("SELECT sense_uid, binary_raw FROM ".$config['table_miscs']." WHERE sense_uid IN($sense_uids_flat) ORDER BY sense_uid;");
        return $this->db_conn->fetchAll(\PDO::FETCH_ASSOC | \PDO::FETCH_GROUP);
    }

    public function get_sense_dials($sense_uids_flat)
    {
        global $config;

        $this->db_conn->query("SELECT sense_uid, binary_raw FROM ".$config['table_dials']." WHERE sense_uid IN($sense_uids_flat) ORDER BY sense_uid;");
        return $this->db_conn->fetchAll(\PDO::FETCH_ASSOC | \PDO::FETCH_GROUP);
    }

    public function get_sense_glosses($sense_uids_flat)
    {
        global $config;

        $this->db_conn->query("SELECT sense_uid, binary_raw, lang, gend FROM ".$config['table_glosses']." WHERE sense_uid IN($sense_uids_flat) ORDER BY sense_uid;");
        return $this->db_conn->fetchAll(\PDO::FETCH_ASSOC | \PDO::FETCH_GROUP);
    }

    public function get_sense_lsources($sense_uids_flat)
    {
        global $config;

        $this->db_conn->query("SELECT sense_uid, binary_raw, lang, type, wasei FROM ".$config['table_lsources']." WHERE sense_uid IN($sense_uids_flat) ORDER BY sense_uid;");
        return $this->db_conn->fetchAll(\PDO::FETCH_ASSOC | \PDO::FETCH_GROUP);
    }

    public function get_sense_stagrs($sense_uids_flat)
    {
        global $config;

        $this->db_conn->query("SELECT S.sense_uid, R.reading_uid, R.binary_raw FROM ".$config['table_stagrs']." AS S JOIN ".$config['table_readings']." AS R USING(reading_uid) WHERE S.sense_uid IN($sense_uids_flat) ORDER BY S.sense_uid;");
        return $this->db_conn->fetchAll(\PDO::FETCH_ASSOC | \PDO::FETCH_GROUP);
    }

    public function get_sense_stagks($sense_uids_flat)
    {
        global $config;

        $this->db_conn->query("SELECT S.sense_uid, K.kanji_uid, K.binary_raw FROM ".$config['table_stagks']." AS S JOIN ".$config['table_kanjis']." AS K USING(kanji_uid) WHERE S.sense_uid IN($sense_uids_flat) ORDER BY S.sense_uid;");
        return $this->db_conn->fetchAll(\PDO::FETCH_ASSOC | \PDO::FETCH_GROUP);
    }

    public function get_sense_raw_ants($sense_uids_flat)
    {
        global $config;

        $this->db_conn->query("SELECT sense_uid, binary_raw FROM ".$config['table_ants_raw']." WHERE sense_uid IN($sense_uids_flat) ORDER BY sense_uid;");
        return $this->db_conn->fetchAll(\PDO::FETCH_ASSOC | \PDO::FETCH_GROUP);
    }

    public function get_sense_raw_xrefs($sense_uids_flat)
    {
        global $config;

        $this->db_conn->query("SELECT sense_uid, binary_raw FROM ".$config['table_xrefs_raw']." WHERE sense_uid IN($sense_uids_flat) ORDER BY sense_uid;");
        return $this->db_conn->fetchAll(\PDO::FETCH_ASSOC | \PDO::FETCH_GROUP);
    }

    //todo this query needs to be sped up
    public function get_sense_resolved_xrefs($sense_uids_flat)
    {
        global $config;

        //$this->db_conn->query("SELECT SA.sense_uid, SA.binary_raw, SA.t_sense_uid, SA.t_kanji_uid, K.binary_raw AS t_kanji_binary_raw, SA.t_reading_uid, R.binary_raw AS t_reading_binary_raw, G.binary_raw AS t_sense_binary_raw FROM ".$config['table_ants']." AS SA JOIN ".$config['table_kanjis']." AS K ON SA.t_kanji_uid=K.kanji_uid JOIN ".$config['table_readings']." AS R ON SA.t_reading_uid=R.reading_uid JOIN (SELECT sense_uid, binary_raw FROM ".$config['table_glosses'].") AS G ON SA.t_sense_uid=G.sense_uid WHERE SA.sense_uid IN($sense_uids_flat) ORDER BY SA.sense_uid;");
        $this->db_conn->query("SELECT * FROM ".$config['table_xrefs']." WHERE sense_uid IN($sense_uids_flat);");
        $xrefs_grouped = $this->db_conn->fetchAll(\PDO::FETCH_ASSOC | \PDO::FETCH_GROUP);
        foreach($xrefs_grouped as $sense_uid_key => &$xrefs)
        {
            foreach($xrefs as &$xref)
            {
                if(!empty($xref['t_kanji_uid']))
                {
                    $this->db_conn->prepare("SELECT kanji_uid, binary_raw FROM ".$config['table_kanjis']." WHERE kanji_uid=:kanji_uid ORDER BY kanji_uid;");
                    $this->db_conn->bindValue(":kanji_uid", $xref['t_kanji_uid'], \PDO::PARAM_INT);
                    $this->db_conn->execute();
                    $xref['t_kanji_binary_raw'] = $this->db_conn->fetch(\PDO::FETCH_ASSOC)['binary_raw'];
                }

                if(!empty($xref['t_reading_uid']))
                {
                    $this->db_conn->prepare("SELECT reading_uid, binary_raw FROM ".$config['table_readings']." WHERE reading_uid=:reading_uid ORDER BY reading_uid;");
                    $this->db_conn->bindValue(":reading_uid", $xref['t_reading_uid'], \PDO::PARAM_INT);
                    $this->db_conn->execute();
                    $xref['t_reading_binary_raw'] = $this->db_conn->fetch(\PDO::FETCH_ASSOC)['binary_raw'];
                }

                if(!empty($xref['t_sense_uid']))
                {
                    $this->db_conn->prepare("SELECT binary_raw FROM ".$config['table_glosses']." WHERE sense_uid=:sense_uid ORDER BY sense_uid LIMIT 1;");
                    $this->db_conn->bindValue(":sense_uid", $xref['t_sense_uid'], \PDO::PARAM_INT);
                    $this->db_conn->execute();
                    $xref['t_sense_binary_raw'] = $this->db_conn->fetch(\PDO::FETCH_ASSOC)['binary_raw'];
                }
            }
            unset($xref);
        }
        unset($xrefs);
        
        return $xrefs_grouped;
    }

    public function get_sense_resolved_ants($sense_uids_flat)
    {
        global $config;

        //$this->db_conn->query("SELECT SX.sense_uid, SX.binary_raw, SX.t_sense_uid, SX.t_kanji_uid, K.binary_raw AS t_kanji_binary_raw, SX.t_reading_uid, R.binary_raw AS t_reading_binary_raw, G.binary_raw AS t_sense_binary_raw FROM ".$config['table_xrefs']." AS SX JOIN ".$config['table_kanjis']." AS K ON SX.t_kanji_uid=K.kanji_uid JOIN ".$config['table_readings']." AS R ON SX.t_reading_uid=R.reading_uid JOIN (SELECT sense_uid, binary_raw FROM ".$config['table_glosses'].") AS G ON SX.t_sense_uid=G.sense_uid WHERE SX.sense_uid IN($sense_uids_flat) ORDER BY SX.sense_uid;");
        $this->db_conn->query("SELECT * FROM ".$config['table_ants']." WHERE sense_uid IN($sense_uids_flat);");
        $ants_grouped = $this->db_conn->fetchAll(\PDO::FETCH_ASSOC | \PDO::FETCH_GROUP);
        foreach($ants_grouped as $sense_uid_key => &$ants)
        {
            foreach($ants as &$ant)
            {
                if(!empty($ant['t_kanji_uid']))
                {
                    $this->db_conn->prepare("SELECT kanji_uid, binary_raw FROM ".$config['table_kanjis']." WHERE kanji_uid=:kanji_uid ORDER BY kanji_uid;");
                    $this->db_conn->bindValue(":kanji_uid", $ant['t_kanji_uid'], \PDO::PARAM_INT);
                    $this->db_conn->execute();
                    $ant['t_kanji_binary_raw'] = $this->db_conn->fetch(\PDO::FETCH_ASSOC)['binary_raw'];
                }

                if(!empty($ant['t_reading_uid']))
                {
                    $this->db_conn->prepare("SELECT reading_uid, binary_raw FROM ".$config['table_readings']." WHERE reading_uid=:reading_uid ORDER BY reading_uid;");
                    $this->db_conn->bindValue(":reading_uid", $ant['t_reading_uid'], \PDO::PARAM_INT);
                    $this->db_conn->execute();
                    $ant['t_reading_binary_raw'] = $this->db_conn->fetch(\PDO::FETCH_ASSOC)['binary_raw'];
                }

                if(!empty($ant['t_sense_uid']))
                {
                    $this->db_conn->prepare("SELECT binary_raw FROM ".$config['table_glosses']." WHERE sense_uid=:sense_uid ORDER BY sense_uid LIMIT 1;");
                    $this->db_conn->bindValue(":sense_uid", $ant['t_sense_uid'], \PDO::PARAM_INT);
                    $this->db_conn->execute();
                    $ant['t_sense_binary_raw'] = $this->db_conn->fetch(\PDO::FETCH_ASSOC)['binary_raw'];
                }
            }
            unset($ant);
        }
        unset($ants);
        
        return $ants_grouped;
    }
}
