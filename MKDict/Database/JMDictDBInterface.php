<?php

namespace MKDict\Database;

/**
 * Interface JMDictEntity
 * 
 * @author Taylor B <taylorbrontario@riseup.net>
 * 
 * @todo need to create an IDList class to typify vars like $sequence_ids_flat
 */
interface JMDictDBInterface
{
    /**
     * Get entries from database given a list of sequence ids
     * 
     * @param string $sequence_ids_flat A flattened list of sequence ids
     * @param int $version_id The version of the dictionary
     * @param string $fetch_style A bitwise combination of PDO flags
     * @param string $order_by The field to order by
     * @param array $fields An array of field names to retrieve from the respective table
     */
    public function get_entries(string $sequence_ids_flat, int $version_id, string $fetch_style, string $order_by, array $fields);
    
    /**
     * Get kanjis from database given a list of sequence ids
     * 
     * @param string $sequence_ids_flat A flattened list of sequence ids
     * @param int $version_id The version of the dictionary
     * @param string $fetch_style A bitwise combination of PDO flags
     * @param string $order_by The field to order by
     * @param array $fields An array of field names to retrieve from the respective table
     */
    public function get_kanjis(string $entry_uids_flat, int $version_id, string $fetch_style, string $order_by, array $fields);
    
    /**
     * Get readings from database given a list of sequence ids
     * 
     * @param string $sequence_ids_flat A flattened list of sequence ids
     * @param int $version_id The version of the dictionary
     * @param string $fetch_style A bitwise combination of PDO flags
     * @param string $order_by The field to order by
     * @param array $fields An array of field names to retrieve from the respective table
     */
    public function get_readings(string $entry_uids_flat, int $version_id, string $fetch_style, string $order_by, array $fields);
    
    /**
     * Get senses from database given a list of sequence ids
     * 
     * @param string $sequence_ids_flat A flattened list of sequence ids
     * @param int $version_id The version of the dictionary
     * @param string $fetch_style A bitwise combination of PDO flags
     * @param string $order_by The field to order by
     * @param array $fields An array of field names to retrieve from the respective table
     */
    public function get_senses(string $entry_uids_flat, int $version_id, string $fetch_style, array $fields);
    
    /**
     * Create new entry
     * 
     * @param \MKDict\Database\JMDictEntry $entry
     * @param int $version_id The dictionary version
     */
    public function new_entry(JMDictEntry $entry, int $version_id);
    
    /**
     * Create new kanji
     * 
     * @param \MKDict\Database\JMDictKanjiElement $kanji
     * @param int $version_id The dictionary version
     */
    public function new_kanji(JMDictKanjiElement $kanji, int $version_id);
    
    /**
     * Create new reading
     * 
     * @param \MKDict\Database\JMDictReadingElement $reading
     * @param int $version_id The dictionary version
     */
    public function new_reading(JMDictReadingElement $reading, int $version_id);
    
    /**
     * Create new sense
     * 
     * @param \MKDict\Database\JMDictSenseElement $sense
     * @param int $version_id The dictionary version
     */
    public function new_sense(JMDictSenseElement $sense, int $version_id);
    
    /**
     * Remove entry
     * 
     * @param int $entry_uid
     * @param int $version_id
     */
    public function remove_entry(int $entry_uid, int $version_id);
    
    /**
     * Remove kanji
     * 
     * @param int $entry_uid
     * @param int $version_id
     */
    public function remove_kanji(int $kanji_uid, int $version_id);
    
    /**
     * Remove reading
     * 
     * @param int $entry_uid
     * @param int $version_id
     */
    public function remove_reading(int $reading_uid, int $version_id);
    
    /**
     * Remove sense
     * 
     * @param int $entry_uid
     * @param int $version_id
     */
    public function remove_sense(int $sense_uid, int $version_id);
}
