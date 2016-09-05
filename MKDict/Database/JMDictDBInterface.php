<?php

namespace MKDict\Database;

interface JMDictDBInterface
{
    public function get_entries($sequence_ids_flat, $version_id, $fetch_style, $order_by, $fields);
    
    public function get_kanjis($entry_uids_flat, $version_id, $fetch_style, $order_by, $fields);
    
    public function get_readings($entry_uids_flat, $version_id, $fetch_style, $order_by, $fields);
    
    public function get_senses($entry_uids_flat, $version_id, $fetch_style, $fields);
    
    public function new_entry(JMDictEntry $entry, $version_id);
    
    public function new_kanji(JMDictKanjiElement $kanji, $version_id);
    
    public function new_reading(JMDictReadingElement $reading, $version_id);
    
    public function new_sense(JMDictSenseElement $sense, $version_id);
    
    public function remove_entry($entry_uid, $version_id);
    
    public function remove_kanji($kanji_uid, $version_id);
    
    public function remove_reading($reading_uid, $version_id);
    
    public function remove_sense($sense_uid, $version_id);
}
