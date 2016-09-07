<?php

namespace MKDict\v1\XML;

use MKDict\DTD\Exception\DTDError;
use MKDict\Unicode\Unicode;
use MKDict\FileResource\Exception\FileIOFailureException;
use MKDict\XML\DictionaryParser;
use MKDict\Security\Security;
use MKDict\Database\DBConnection;

use MKDict\v1\Database\JMDictDB;
use MKDict\v1\Database\JMDictEntry;
use MKDict\v1\Database\JMDictKanjiElement;
use MKDict\v1\Database\JMDictReadingElement;
use MKDict\v1\Database\JMDictSenseElement;
use MKDict\v1\Database\JMDictStringElement;
use MKDict\v1\Database\JMDictElementList;

/**
* The versioned parser for the JMDict dictionary.
* 
* @author Taylor B <taylorbrontario@riseup.net>
* 
* @todo the db layer already has a bufffer. probably no performance benefit to buffering here as well
*/
class JMDictParser extends DictionaryParser
{
    /**@var string This property is late static binded from the parent. Every implementation of DictionaryParser must implement this variable */
    protected static $ENTITY_ID = 'd73ilHD34h8dyq';
    
    protected $attribs;
    protected $entry_buffer;
    protected $duplicate_sequence_id;

    const ELEMENT_ENTRY = 'entry';
    const ELEMENT_ENTRY_SEQUENCE = 'ent_seq';
    const ELEMENT_READING = 'r_ele';
    const ELEMENT_READING_BINARY =  'reb';
    const ELEMENT_READING_NOKANJI = 're_nokanji';
    const ELEMENT_READING_RESTRICTION = 're_restr';
    const ELEMENT_READING_INFO = 're_inf';
    const ELEMENT_READING_PRI = 're_pri';
    const ELEMENT_KANJI = 'k_ele';
    const ELEMENT_KANJI_BINARY = 'keb';
    const ELEMENT_KANJI_INFO = 'ke_inf';
    const ELEMENT_KANJI_PRI = 'ke_pri';
    const ELEMENT_SENSE = 'sense';
    const ELEMENT_POS = 'pos';
    const ELEMENT_SENSE_GLOSS = 'gloss';        
    const ELEMENT_SENSE_STAGK = 'stagk';
    const ELEMENT_SENSE_STAGR = 'stagr';
    const ELEMENT_SENSE_XREF = 'xref';
    const ELEMENT_SENSE_ANT = 'ant';
    const ELEMENT_SENSE_FIELD = 'field';
    const ELEMENT_SENSE_MISC = 'misc';
    const ELEMENT_SENSE_INF = 's_inf';
    const ELEMENT_SENSE_LSOURCE = 'lsource';
    const ELEMENT_SENSE_DIAL = 'dial';
    const ELEMENT_GLOSS_PRI = 'pri';
    const ELEMENT_JMDOCUMENT = 'JMdict';
    
    /**
     * Return an array of the parser pass functions that need to be called as part of the parsing process
     * 
     * @return array An array of function names
     */
    public function get_parser_passes()
    {
        return array_merge(parent::get_parser_passes(), array("parser_pass_2"));
    }

    /**
     * character_data_handler() wrapper
     *
     * @param resource $parser   The native parser resource
     * @param resource $name   The character data
     * 
     * @return void
     */
    public function character_data_handler(resource $parser, string $data)
    {
        $this->character_buffer .= strval($data);
    }

    /**
     * start_element_handler() wrapper
     *
     * @param resource $parser   The native parser resource
     * @param resource $name   The elements name
     * @param array $attribs   The elements attributes
     * 
     * @return void
     */
    public function start_element_handler(resource $parser, string $name, array $attribs)
    {
        global $options;

        $this->attribs = $attribs;

        $is_root = false;
        //check that first element is the document root, then skip it
        if($this->check_doc_name)
        {
            if($name !== $this->dtd->document_name)
            {
                throw new DTDError("Document name missing");
            }
            $is_root = true;
        }
        $this->check_doc_name = false;

        if(!isset($this->dtd->elements[$name]))
        {
           throw new DTDError("Unrecognized element name: $name");
        }

        if(!$is_root)
        {
            switch($name)
            {
                case self::ELEMENT_ENTRY:
                    $this->entry = new JMDictEntry();
                    break;

                case self::ELEMENT_READING:
                    $this->reading = new JMDictReadingElement();
                    break;

                case self::ELEMENT_KANJI:
                    $this->kanji = new JMDictKanjiElement();
                    break;

                case self::ELEMENT_SENSE:
                    $this->sense = new JMDictSenseElement();
                    $this->sense->sense_index = ++$this->entry->sense_count;
                    break;

                case self::ELEMENT_JMDOCUMENT:
                case self::ELEMENT_ENTRY_SEQUENCE:
                case self::ELEMENT_READING_NOKANJI:
                case self::ELEMENT_READING_RESTRICTION:
                case self::ELEMENT_READING_INFO:
                case self::ELEMENT_READING_PRI:
                case self::ELEMENT_READING_BINARY:
                case self::ELEMENT_KANJI_INFO:
                case self::ELEMENT_KANJI_PRI:
                case self::ELEMENT_KANJI_BINARY:
                case self::ELEMENT_SENSE_GLOSS:
                case self::ELEMENT_POS:
                case self::ELEMENT_SENSE_FIELD:
                case self::ELEMENT_SENSE_MISC:
                case self::ELEMENT_SENSE_STAGK:
                case self::ELEMENT_SENSE_STAGR:
                case self::ELEMENT_SENSE_XREF:
                case self::ELEMENT_SENSE_ANT:
                case self::ELEMENT_SENSE_INF:
                case self::ELEMENT_SENSE_LSOURCE:
                case self::ELEMENT_SENSE_DIAL:
                case self::ELEMENT_GLOSS_PRI:
                    break;

                default:
                    throw new DTDError("Unrecognized element name: $name");
                    break;
            }
        }
    }


    /**
     * end_element_handler() wrapper
     *
     * @param resource $parser   The native parser resource
     * @param resource $name   The elements name
     * 
     * @return void
     */
    public function end_element_handler(resource $parser, string $name)
    {
        global $config;
        
        switch($name)
        {
            case self::ELEMENT_ENTRY:
                if($this->duplicate_sequence_id)
                {
                    $this->logger->duplicate_sequence_id($this->entry);
                    $this->entry->invalid_data = true;
                    $this->duplicate_sequence_id = false;
                }
                else
                {
                    $this->insert_entry();
                }
                break;

            case self::ELEMENT_ENTRY_SEQUENCE:
                $this->entry->sequence_id = $this->validate_int($this->character_buffer);
                //NOTE this won't actually ensure each sequence_id is unique in the document globally, only unique up to the current buffer contents
                //but it ensures we wont overwrite an entry in the buffer and thereby fail to record duplicate entries in the tmp file later on
                if(in_array($this->entry->sequence_id, $this->sequence_ids))
                {
                    $this->duplicate_sequence_id = true;
                }
                else
                {
                    $this->sequence_ids[] = $this->entry->sequence_id;
                    if(count($this->sequence_ids) >= $config['int_array_max_size'])
                    {
                        $this->write_sequence_ids_to_file();
                    }
                }
                break;

            case self::ELEMENT_READING:
                $this->entry->readings->append($this->reading);
                $this->reading = null;
                break;

            case self::ELEMENT_KANJI:
                $this->entry->kanjis->append($this->kanji);
                $this->kanji = null;
                break;

            case self::ELEMENT_SENSE:
                $this->entry->senses->append($this->sense);
                $this->sense = null;
                break;

            case self::ELEMENT_READING_BINARY:
                $this->reading->binary_raw = $this->clean_raw_text($this->character_buffer);
                $this->set_binary_fields($this->reading);
                break;

            case self::ELEMENT_KANJI_BINARY:
                $this->kanji->binary_raw = $this->clean_raw_text($this->character_buffer);
                $this->set_binary_fields($this->kanji);
                break;

            case self::ELEMENT_READING_NOKANJI:
                $this->reading->b_no_kanji = 1;
                break;

            case self::ELEMENT_READING_RESTRICTION:
                $this->reading->restrs->append(array('binary_raw' => $this->clean_raw_text($this->character_buffer)));
                break;

            case self::ELEMENT_READING_INFO:
                $val = $this->clean_raw_text($this->character_buffer);
                $this->reading->infos->append(array('binary_raw' => $val));
                break;

            case self::ELEMENT_KANJI_INFO:
                $this->kanji->infos->append(array('binary_raw' => $this->clean_raw_text($this->character_buffer)));
                break;

            case self::ELEMENT_SENSE_GLOSS:
                $gloss = array();
                $gloss['binary_raw'] = $this->clean_raw_text($this->character_buffer);
                $gloss['lang'] = isset($this->attribs['xml:lang']) ?  $this->attribs['xml:lang'] : "";
                $gloss['gend'] = isset($this->attribs['g_gend']) ? $this->attribs['g_gend'] : "";
                $this->sense->glosses->append($gloss);
                break;

            case self::ELEMENT_POS:
                $this->sense->poses->append(array('binary_raw' => $this->clean_raw_text($this->character_buffer)));
                break;

            case self::ELEMENT_SENSE_FIELD:
                $this->sense->fields->append(array('binary_raw' => $this->clean_raw_text($this->character_buffer)));
                break;

            case self::ELEMENT_SENSE_MISC:
                $this->sense->miscs->append(array('binary_raw' => $this->clean_raw_text($this->character_buffer)));
                break;

            case self::ELEMENT_SENSE_STAGR:
                $this->sense->stagrs->append(array('binary_raw' => $this->clean_raw_text($this->character_buffer)));
                break;

            case self::ELEMENT_SENSE_STAGK:
                $this->sense->stagks->append(array('binary_raw' => $this->clean_raw_text($this->character_buffer)));
                break;

            case self::ELEMENT_SENSE_XREF:
                $this->sense->xrefs->append(array('binary_raw' => $this->clean_raw_text($this->character_buffer)));
                break;

            case self::ELEMENT_SENSE_ANT:
                $this->sense->ants->append(array('binary_raw' => $this->clean_raw_text($this->character_buffer)));
                break;

            case self::ELEMENT_SENSE_INF:
                $this->sense->infos->append(array('binary_raw' => $this->clean_raw_text($this->character_buffer)));
                break;

            case self::ELEMENT_SENSE_LSOURCE:
                $lsource = array();
                $lsource['binary_raw'] = $this->clean_raw_text($this->character_buffer, true);
                $lsource['lang'] = isset($this->attribs['xml:lang']) ? $this->attribs['xml:lang'] : "";
                $lsource['type'] = isset($this->attribs['ls_type']) ? $this->attribs['ls_type'] : "";
                $lsource['wasei'] = isset($this->attribs['ls_wasei']) ? ( strtoupper(substr($this->attribs['ls_wasei'],0,1)) === "Y" ? "Y" : "" ) : "";
                $this->sense->lsources->append($lsource);
                break;

            case self::ELEMENT_SENSE_DIAL:
                $this->sense->dials->append(array('binary_raw' => $this->clean_raw_text($this->character_buffer)));
                break;

            case self::ELEMENT_READING_PRI:
                $this->reading->pris->append(array('binary_raw' => $this->clean_raw_text($this->character_buffer)));
                break;

            case self::ELEMENT_KANJI_PRI:
                $this->kanji->pris->append(array('binary_raw' => $this->clean_raw_text($this->character_buffer)));
                break;

            case self::ELEMENT_JMDOCUMENT:
                $this->write_sequence_ids_to_file();
                break;

            case self::ELEMENT_GLOSS_PRI:
                break;

            default:
                throw new DTDError("Unrecognized element name: $name");
                break;
        }
        $this->character_buffer = "";
        $this->attribs = array();
    }


    /**
     * Prepares raw data to be set as the binary fields of a text element
     *
     * @param JMDictStringElement $element   The XML element
     * 
     * @return void
     */
    protected function set_binary_fields(JMDictStringElement $element)
    {
        $binary_nfc = Unicode::normalize_text($element->binary_raw, Unicode::UTF_NORMALIZE_NFC, self::$ENTITY_ID);
        $element->binary_nfc = $element->binary_raw === $binary_nfc ? null : $binary_nfc;

        $binary_nfd = Unicode::normalize_text($element->binary_raw, Unicode::UTF_NORMALIZE_NFD, self::$ENTITY_ID);
        $element->binary_nfd = $element->binary_raw === $binary_nfd ? null : $binary_nfd;

        $binary_nfkc = Unicode::normalize_text($element->binary_raw, Unicode::UTF_NORMALIZE_NFKC, self::$ENTITY_ID);
        $element->binary_nfkc = $element->binary_raw === $binary_nfkc ? null : $binary_nfkc;

        $binary_nfkd = Unicode::normalize_text($element->binary_raw, Unicode::UTF_NORMALIZE_NFKD, self::$ENTITY_ID);
        $element->binary_nfkd = $element->binary_raw === $binary_nfkd ? null : $binary_nfkd;

        $binary_nfd_casefolded = Unicode::normalize_text($element->binary_raw, Unicode::UTF_NORMALIZE_NFD_CASEFOLD, self::$ENTITY_ID);
        $element->binary_nfd_casefolded = $element->binary_raw === $binary_nfd_casefolded ? null : $binary_nfd_casefolded;

        $binary_nfkd_casefolded = Unicode::normalize_text($element->binary_raw, Unicode::UTF_NORMALIZE_NFKD_CASEFOLD, self::$ENTITY_ID);
        $element->binary_nfkd_casefolded = $element->binary_raw === $binary_nfkd_casefolded ? null : $binary_nfkd_casefolded;
    }

    /**
     * Cleans up raw text
     *
     * @param string $text The input string
     * @param bool $can_be_empty Whether or not the input string can be empty to be considered valid data
     * 
     * @return string
     */
    protected function clean_raw_text(string $text, bool $can_be_empty = false)
    {
        $result = $this->validate_string($text, $can_be_empty);
        if($result === false)
        {
            $this->entry->invalid_data = true;
        }
        $val = trim($this->strip_entity_tags($result));
        return $val;
    }

    /**
     * Validate the current entry
     *
     * @return bool True if the entry's data is valid, false otherwise
     */
    protected function entry_validate()
    {
        if($this->entry->invalid_data)
        {
            return false;
        }

        if(empty($this->entry->sequence_id)) //remember boolean false evaluates to empty
        {
            $this->logger->sequence_id_missing_or_invalid($this->entry);
            return false;
        }

        if(empty($this->entry->readings))
        {
            $this->logger->missing_reading_element($this->entry);
            return false;
        }

        if(empty($this->entry->senses))
        {
            $this->logger->missing_sense_element($this->entry);
            return false;
        }

        foreach($this->entry->readings as $reading)
        {
            if(empty($reading->binary_raw))
            {
                $this->logger->missing_binary_field($this->entry);
                return false;
            }
        }

        foreach($this->entry->kanjis as $kanji)
        {
            if(empty($kanji->binary_raw))
            {
                $this->logger->missing_binary_field($this->entry);
                return false;
            }
        }

        return true;
    }

    /**
     * Insert the current entry into the db
     *
     * @return void
     */
    protected function insert_entry()
    {
        //if the entry does not contain valid data, it's safe now to skip this entry's processing
        if(false === $this->entry_validate())
        {
            return;
        }

        //set pos child elements for each sense. see comment on the pos element in the dictionary's xml file for explanation before inserting senses
        $pos = "";
        foreach($this->entry->senses as $sense)
        {
            if(!empty($sense->poses))
            {
                $pos = $sense->poses;
            }

            if(!empty($pos))
            {
                $sense->poses = $pos;
            }
        }

        if($this->version_id == 1)
        {
            $this->insert_new_entry();
        }
        else
        {
            $this->merge_entry();
        }
    }

    /**
     * Write the merge buffer to the database
     * 
     * @return void
     */
    protected function write_merge_buffer()
    {
        global $config;
        
        $sequence_ids_flat = DBConnection::flatten_array(array_keys($this->entry_buffer));

        $entries = $this->jmdb->get_entries($sequence_ids_flat);
        $entry_uids_flat = DBConnection::flatten_array(array_column($entries, "entry_uid"));
        
        //kanjis
        $kanjis = $this->jmdb->get_kanjis($entry_uids_flat, $this->version_id, \PDO::FETCH_ASSOC | \PDO::FETCH_GROUP, "entry_uid");

        $kanji_uids = array();
        foreach($kanjis as $kanji)
        {
            $kanji_uids = array_merge($kanji_uids, array_column($kanji, "kanji_uid"));
        }
        $kanji_uids_flat = DBConnection::flatten_array($kanji_uids);
        unset($kanji_uids);

        $kanji_pris = $this->jmdb->get_kanji_pris($kanji_uids_flat);
        $kanji_infos = $this->jmdb->get_kanji_infos($kanji_uids_flat);

        //readings
        $readings = $this->jmdb->get_readings($entry_uids_flat, $this->version_id, \PDO::FETCH_ASSOC | \PDO::FETCH_GROUP, "entry_uid", array("binary_raw", "nokanji"));

        $reading_uids = array();
        foreach($readings as $reading)
        {
            $reading_uids = array_merge($reading_uids, array_column($reading, "reading_uid"));
        }
        $reading_uids_flat = DBConnection::flatten_array($reading_uids);
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
        $sense_uids_flat = DBConnection::flatten_array($sense_uids);
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
        $sense_ants = $this->jmdb->get_sense_raw_ants($sense_uids_flat);
        $sense_xrefs = $this->jmdb->get_sense_raw_xrefs($sense_uids_flat);

        //NOTE: this_ is for data being imported, that_ is for data from the db that is being imported against
        foreach($entries as $entry)
        {
            $this_sequence_id = $entry['sequence_id'];
            $this_entry_uid = $entry['entry_uid'];

            $this_entry = $this->entry_buffer[$this_sequence_id];
            $this_entry->entry_uid = $this_entry_uid;
            $this_entry->sequence_id = $this_sequence_id;
            unset($this->entry_buffer[$this_sequence_id]);

            $expired_kanjis = $expired_readings = $expired_senses = array();

            //kanjis
            if(isset($kanjis[$this_entry_uid]))
            {
                $those_kanjis = $kanjis[$this_entry_uid];
                unset($kanjis[$this_entry_uid]);

                foreach($those_kanjis as $that_kanji)
                {
                    $that_kanji_obj = new JMDictKanjiElement();
                    $that_kanji_obj->kanji_uid = $that_kanji['kanji_uid'];
                    $that_kanji_obj->binary_raw = $that_kanji['binary_raw'];

                    if(isset($kanji_pris[$that_kanji['kanji_uid']]))
                    {
                        $that_kanji_obj->pris = new JMDictElementList($kanji_pris[$that_kanji['kanji_uid']]);
                        unset($kanji_pris[$that_kanji['kanji_uid']]);
                    }

                    if(isset($kanji_infos[$that_kanji['kanji_uid']]))
                    {
                        $that_kanji_obj->infos = new JMDictElementList($kanji_infos[$that_kanji['kanji_uid']]);
                        unset($kanji_infos[$that_kanji['kanji_uid']]);
                    }

                    //find corresponding this_kanji then compare to that_kanji
                    foreach($this_entry->kanjis as $this_kanji)
                    {
                        if(JMDictKanjiElement::is_equal($this_kanji, $that_kanji_obj))
                        {
                            $this_kanji->is_new = false;
                            $this_kanji->kanji_uid = $that_kanji_obj->kanji_uid;
                            continue 2;
                        }
                    }

                    //if we made it here, then we have a kanji from the db (that_kanji) that is not present the current entry ie a kanji was removed
                    $expired_kanjis[] = $that_kanji_obj;
                }
            }

            //readings
            $those_readings = $readings[$this_entry_uid];
            unset($readings[$this_entry_uid]);

            foreach($those_readings as $that_reading)
            {
                $that_reading_obj = new JMDictReadingElement();
                $that_reading_obj->reading_uid = $that_reading['reading_uid'];
                $that_reading_obj->binary_raw = $that_reading['binary_raw'];
                $that_reading_obj->b_no_kanji = intval($that_reading['nokanji']);

                if(isset($reading_restrs[$that_reading['reading_uid']]))
                {
                    $that_reading_obj->restrs = new JMDictElementList($reading_restrs[$that_reading['reading_uid']]);
                    unset($reading_restrs[$that_reading['reading_uid']]);
                }

                if(isset($reading_pris[$that_reading['reading_uid']]))
                {
                    $that_reading_obj->pris = new JMDictElementList($reading_pris[$that_reading['reading_uid']]);
                    unset($reading_pris[$that_reading['reading_uid']]);
                }

                if(isset($reading_infos[$that_reading['reading_uid']]))
                {
                    $that_reading_obj->infos = new JMDictElementList($reading_infos[$that_reading['reading_uid']]);
                    unset($reading_infos[$that_reading['reading_uid']]);
                }

                foreach($this_entry->readings as $this_reading)
                {
                    if(JMDictReadingElement::is_equal($this_reading, $that_reading_obj))
                    {
                        $this_reading->is_new = false;
                        $this_reading->reading_uid = $that_reading_obj->reading_uid;
                        continue 2;
                    }
                }

                $expired_readings[] = $that_reading_obj;
            }

            //senses
            $those_senses = $senses[$this_entry_uid];
            unset($senses[$this_entry_uid]);

            foreach($those_senses as $that_sense)
            {
                $that_sense_obj = new JMDictSenseElement();
                $that_sense_obj->sense_uid = $that_sense['sense_uid'];

                if(isset($sense_infos[$that_sense['sense_uid']]))
                {
                    $that_sense_obj->infos = new JMDictElementList($sense_infos[$that_sense['sense_uid']]);
                    unset($sense_infos[$that_sense['sense_uid']]);
                }

                if(isset($sense_poses[$that_sense['sense_uid']]))
                {
                    $that_sense_obj->poses = new JMDictElementList($sense_poses[$that_sense['sense_uid']]);
                    unset($sense_poses[$that_sense['sense_uid']]);
                }

                if(isset($sense_fields[$that_sense['sense_uid']]))
                {
                    $that_sense_obj->fields = new JMDictElementList($sense_fields[$that_sense['sense_uid']]);
                    unset($sense_fields[$that_sense['sense_uid']]);
                }

                if(isset($sense_miscs[$that_sense['sense_uid']]))
                {
                    $that_sense_obj->miscs = new JMDictElementList($sense_miscs[$that_sense['sense_uid']]);
                    unset($sense_miscs[$that_sense['sense_uid']]);
                }

                if(isset($sense_dials[$that_sense['sense_uid']]))
                {
                    $that_sense_obj->dials = new JMDictElementList($sense_dials[$that_sense['sense_uid']]);
                    unset($sense_dials[$that_sense['sense_uid']]);
                }

                if(isset($sense_glosses[$that_sense['sense_uid']]))
                {
                    $that_sense_obj->glosses = new JMDictElementList($sense_glosses[$that_sense['sense_uid']]);
                    unset($sense_glosses[$that_sense['sense_uid']]);
                }

                if(isset($sense_lsources[$that_sense['sense_uid']]))
                {
                    $that_sense_obj->lsources = new JMDictElementList($sense_lsources[$that_sense['sense_uid']]);
                    unset($sense_lsources[$that_sense['sense_uid']]);
                }

                if(isset($sense_stagrs[$that_sense['sense_uid']]))
                {
                    $that_sense_obj->stagrs = new JMDictElementList($sense_stagrs[$that_sense['sense_uid']]);
                    unset($sense_stagrs[$that_sense['sense_uid']]);
                }

                if(isset($sense_stagks[$that_sense['sense_uid']]))
                {
                    $that_sense_obj->stagks = new JMDictElementList($sense_stagks[$that_sense['sense_uid']]);
                    unset($sense_stagks[$that_sense['sense_uid']]);
                }

                if(isset($sense_ants[$that_sense['sense_uid']]))
                {
                    $that_sense_obj->ants = new JMDictElementList($sense_ants[$that_sense['sense_uid']]);
                    unset($sense_ants[$that_sense['sense_uid']]);
                }

                if(isset($sense_xrefs[$that_sense['sense_uid']]))
                {
                    $that_sense_obj->xrefs = new JMDictElementList($sense_xrefs[$that_sense['sense_uid']]);
                    unset($sense_xrefs[$that_sense['sense_uid']]);
                }

                foreach($this_entry->senses as $this_sense)
                {
                    if(JMDictSenseElement::is_equal($this_sense, $that_sense_obj))
                    {
                        $this_sense->is_new = false;
                        $this_sense->sense_uid = $that_sense_obj->sense_uid;
                        continue 2;
                    }
                }
                $expired_senses[] = $that_sense_obj;
            }

            foreach($expired_kanjis as $expired_kanji)
            {
                $this->jmdb->remove_kanji($expired_kanji->kanji_uid, $this->version_id);
                if($this->version_id > 1)
                {
                    $this->logger->expired_kanji($expired_kanji);
                }
            }

            foreach($this_entry->kanjis as $this_kanji)
            {
                if(true === $this_kanji->is_new)
                {
                    $this_kanji->entry_uid = $this_entry_uid;
                    $this_kanji->kanji_uid = $this->jmdb->new_kanji($this_kanji, $this->version_id);
                    if($this->version_id > 1)
                    {
                        $this->logger->new_kanji($this_kanji);
                    }
                }
            }

            foreach($expired_readings as $expired_reading)
            {
                $this->jmdb->remove_reading($expired_reading->reading_uid, $this->version_id);
                $this->logger->expired_reading($expired_reading, $this->version_id);
            }

            foreach($this_entry->readings as $this_reading)
            {
                if(true === $this_reading->is_new)
                {
                    $this_reading->entry_uid = $this_entry_uid;
                    $this_reading->reading_uid = $this->jmdb->new_reading($this_reading, $this->version_id);
                    if($this->version_id > 1)
                    {
                        $this->logger->new_reading($this_reading, $this->version_id);
                    }
                }
            }

            foreach($expired_senses as $expired_sense)
            {
                $this->jmdb->remove_sense($expired_sense->sense_uid, $this->version_id);
                $this->logger->expired_sense($expired_sense, $this->version_id);
            }
            unset($expired_senses);

            foreach($this_entry->senses as $this_sense)
            {
                if(true === $this_sense->is_new)
                {
                    $this_sense->entry_uid = $this_entry_uid;
                    $this_sense->reading_uid = $this->jmdb->new_sense($this_sense, $this->version_id);
                    if($this->version_id > 1)
                    {
                        $this->logger->new_sense($this_sense);
                    }
                }
            }

            //re-establish element references
            foreach($this_entry->readings as $this_reading)
            {
                if(false === $this_reading->is_new )
                    continue;

                if(!empty($this_reading->restrs))
                {
                    foreach($this_reading->restrs as &$restr)
                    {
                        foreach($this_entry->kanjis as $this_kanji)
                        {
                            if($this->binary_compare($restr['binary_raw'], $this_kanji->binary_raw))
                            {
                                $restr['kanji_uid'] = $this_kanji->kanji_uid;
                                continue 2;
                            }
                        }
                        //if we get here that means the target kanji of a reading restriction was not found
                        $this->logger->restr_target_not_found($this_entry, $this_reading);
                    }
                    unset($restr);
                    $this->jmdb->update_restrs($this_reading);
                }
            }

            foreach($this_entry->senses as $this_sense)
            {
                if(false === $this_sense->is_new)
                    continue;

                if(!empty($this_sense->stagrs))
                {
                    foreach($this_sense->stagrs as &$stagr)
                    {
                        foreach($this_entry->readings as $this_reading)
                        {
                            if($this->binary_compare($stagr['binary_raw'], $this_reading->binary_raw))
                            {
                                $stagr['reading_uid'] = $this_reading->reading_uid;
                                continue 2;
                            }
                        }
                        $this->processing_errors_collection->stagr_target_not_found($this_entry, $this_sense);
                    }
                    unset($stagr);
                    $this->jmdb->update_stagrs($this_sense);
                }

                if(!empty($this_sense->stagks))
                {
                    foreach($this_sense->stagks as &$stagk)
                    {
                        foreach($this_entry->kanjis as $this_kanji)
                        {
                            if($this->binary_compare($stagk['binary_raw'], $this_kanji->binary_raw))
                            {
                                $stagk['kanji_uid'] = $this_kanji->kanji_uid;
                                continue 2;
                            }
                        }
                        $this->processing_errors_collection->stagk_target_not_found($this_entry, $this_sense);
                    }
                    unset($stagk);
                    $this->jmdb->update_stagks($this_sense);
                }
            }
        }

        //any remaining entries not already unset in the entry_buffer are new entries
        foreach($this->entry_buffer as $entry)
        {
            $entry->entry_uid = $this->insert_new_entry($entry);
            if($this->version_id > 1)
            {
                $this->logger->new_entry($entry);
            }
        }

        $this->entry_buffer = array();
    }

    /**
     * Merge current entry into the DB
     */
    protected function merge_entry()
    {
        global $config;
        
        $this->entry->sequence_id = $this->entry->sequence_id;
        $this->entry_buffer[$this->entry->sequence_id] = $this->entry;

        if(count($this->entry_buffer) > $config['element_buffer_size'])
        {
            $this->write_merge_buffer();
        }
    }

    /**
     * Insert a new entry into the db
     * 
     * @param JMDictEntry $entry
     * @return void
     */
    protected function insert_new_entry(JMDictEntry $entry = null)
    {
        if(empty($entry))
        {
            $entry = &$this->entry;
        }
        
        $entry->entry_uid = $this->jmdb->new_entry($this->entry, $this->version_id);

        //insert kanjis
        foreach($entry->kanjis as $kanji)
        {
            $kanji->entry_uid = $entry->entry_uid;
            $kanji->kanji_uid = $this->jmdb->new_kanji($kanji, $this->version_id);
        }

        //insert readings
        foreach($entry->readings as $reading)
        {
            $reading->entry_uid = $entry->entry_uid;
            $reading->reading_uid = $this->jmdb->new_reading($reading, $this->version_id);
        }

        //identify the kanjis to which the readings are restricted
        foreach($entry->readings as $reading)
        {
            if(!empty($reading->restrs))
            {
                foreach($reading->restrs as &$restr)
                {
                    foreach($entry->kanjis as $kanji)
                    {
                        if($this->binary_compare($restr['binary_raw'], $kanji->binary_raw))
                        {
                            $restr['kanji_uid'] = $kanji->kanji_uid;
                            continue 2;
                        }
                    }
                    $this->logger->restr_target_not_found($entry, $reading);
                }
                unset($restr);
                $this->jmdb->update_restrs($reading);
            }
        }

        //insert senses
        foreach($entry->senses as $sense)
        {
            $sense->entry_uid = $entry->entry_uid;
            $sense->sense_uid = $this->jmdb->new_sense($sense, $this->version_id);
        }

        foreach($entry->senses as $sense)
        {
            if(!empty($sense->stagrs))
            {
                foreach($sense->stagrs as &$stagr)
                {
                    foreach($entry->readings as $reading)
                    {
                        if($this->binary_compare($stagr['binary_raw'], $reading->binary_raw))
                        {
                            $stagr['reading_uid'] = $reading->reading_uid;
                            $stagr['sense_uid'] = $sense->sense_uid;
                            continue 2;
                        }
                    }
                    $this->processing_errors_collection->stagr_target_not_found($entry, $sense);
                }
                unset($stagr);
                $this->jmdb->update_stagrs($sense->stagrs);
            }

            if(!empty($sense->stagks))
            {
                foreach($sense->stagks as &$stagk)
                {
                    foreach($entry->kanjis as $kanji)
                    {
                        if($this->binary_compare($stagk['binary_raw'], $kanji->binary_raw))
                        {
                            $stagk['kanji_uid'] = $kanji->kanji_uid;
                            $stagk['sense_uid'] = $sense->sense_uid;
                            continue 2;
                        }
                    }
                    $this->processing_errors_collection->stagk_target_not_found($entry, $sense);
                }
                unset($stagk);
                $this->jmdb->update_stagks($sense->stagks);
            }
        }

        return $entry->entry_uid;
    }

    /**
     * Compares to binarys strings for eqaulity
     * 
     * @param string $binary1 Input string
     * @param string $binary2 Input string
     * 
     * @return bool True if the binary strings are equal, false otherwise
     */
    protected function binary_compare($binary1, $binary2)
    {
        return $binary1 === $binary2 ? true : false;
    }


    /**
     * The parser pass of this class
     * 
     * @return void
     * 
     * @throws FileIOFailureException If reading temp file fails
     */
    public function parser_pass_2()
    {
        global $config;
        
        //write any outstanding data to the db
        $this->jmdb->write_all_buffers();

        //aggregate the reference types
        $reference_types = array();

        $ants = $this->jmdb->flush_ants();
        foreach($ants as &$ant)
        {
            $ant['ref_type'] = "ant";
            $reference_types[] = $ant;
        }
        unset($ant);

        $xrefs = $this->jmdb->flush_xrefs();
        foreach($xrefs as &$xref)
        {
            $xref['ref_type'] = "xref";
            $reference_types[] = $xref;
        }
        unset($xref);

        //insert reference types into db
        foreach($reference_types as $reference_type)
        {
            $values = Security::explode_safe($config['jmdict_ref_field_delimiter'], $reference_type['binary_raw'], "string");
            
            $results = "";
            $reference_format = count($values);
            switch($reference_format)
            {
                case 1:
                    //the reference is either a reading or kanji entry
                    $results = $this->jmdb->k_or_r_search($this->version_id, trim(strval($values[0])), 0);
                    break;

                case 2:
                    if(is_numeric($values[1]))
                    {
                        //if the second value is a number, then the first value is either a reb or keb
                        $results = $this->jmdb->k_or_r_search($this->version_id, trim(strval($values[0])), intval(trim($values[1])));
                    }
                    else
                    {
                        //else the first value must be a keb and the second value a reb
                        $results = $this->jmdb->k_and_r_search($this->version_id, trim(strval($values[0])), trim(strval($values[1])), 0);
                    }
                    break;

                case 3:
                    if(!is_numeric($values[2]))
                    {
                        //$this->logger->invalid_reference_type($reference_type, "Invalid reference element. 3rd component is not a valid integer");
                        $results = false;
                    }
                    else
                    {
                        $results = $this->jmdb->k_and_r_search($this->version_id, trim(strval($values[0])), trim(strval($values[1])), intval(trim($values[2])));
                    }
                    break;

                default:
                    $this->logger->invalid_reference_type($reference_type, "Invalid reference element format:\n");
                    continue;
                    break;
            }

            switch($reference_type['ref_type'])
            {
                case "ant":
                    $insertion_point = $config['table_ants'];
                    break;

                case "xref":
                    $insertion_point = $config['table_xrefs'];
                    break;
            }

            if(false === $results)
            {
                //try the reference type again but with the raw string data, or in other words, assume now the delimiter character used is part of the string data
                if(false === $results = $this->jmdb->k_or_r_search($this->version_id, trim(strval($reference_type['binary_raw'])), 0))
                {
                    $this->logger->invalid_reference_type($reference_type, "Invalid reference type");
                    continue;
                }
            }

            $this->jmdb->new_reference_types($reference_type['sense_uid'], $results, $insertion_point, $reference_type['binary_raw']);
        }

        $this->jmdb->write_all_buffers(true);
        
        if($this->version_id > 1)
        {
            $this->write_merge_buffer();
            $this->tmp_file->rewind();
            $sequence_ids_full = array();
            
            while(false !== $seq_ids_line = $this->tmp_file->fgets())
            {
                $sequence_ids_ary = Security::explode_safe(",", trim($seq_ids_line));
                $sequence_ids_flat = Security::flatten_array($sequence_ids_ary);
                $expired_entries = $this->jmdb->get_diff_entry_uids($sequence_ids_flat);

                foreach($expired_entries as $expired_entry_row)
                {
                    $this->jmdb->remove_entry($expired_entry_row['entry_uid'], $this->version_id);
                    $expired_entry = new JMDictEntry();
                    $expired_entry->entry_uid = $expired_entry_row['entry_uid'];
                    $expired_entry->sequence_id = $expired_entry_row['sequence_id'];
                    $this->logger->expired_entry($expired_entry);
                }

                $sequence_ids_full = array_merge($sequence_ids_full, $sequence_ids_ary);
            }

            //check the full list of sequence_ids for duplicates and then logg the duplicate's presence
            $duplicate_sequence_ids = array_filter(array_count_values($sequence_ids_full), function($value){ return $value > 1 ? true : false;});
            foreach($duplicate_sequence_ids as $sequence_id)
            {
                $this->logger->duplicate_sequence_id(null, $sequence_id);
            }

            if(!$this->tmp_file->feof())
            {
                throw new FileIOFailureException("Unexepted loop read failure");
            }
        }
        $this->tmp_file->unlink();
        $this->jmdb->update_uid_counter();
    }

    /**
     * Write sequence_ids to temp file for later
     * 
     * @return void
     */
    protected function write_sequence_ids_to_file()
    {
        $ids_flat = Security::flatten_array($this->sequence_ids);
        $this->tmp_file->write("$ids_flat\n");
        $this->sequence_ids = array();
    }

}