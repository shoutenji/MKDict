<?php

namespace MKDict\v1\Exporter\XML;

use MKDict\Database\DBConnection;
use MKDict\Exporter\XML\XMLExporter;
use MKDict\Database\JMDictEntity;
use MKDict\FileResource\FileInfo;
use MKDict\FileResource\ByteStreamFileResource;

use MKDict\v1\Database\JMDictDB;
use MKDict\v1\Database\JMDictEntry;
use MKDict\v1\Database\JMDictKanjiElement;
use MKDict\v1\Database\JMDictReadingElement;
use MKDict\v1\Database\JMDictSenseElement;

/**
 * XML Exporter for version 1
 * 
 * @author Taylor B <taylorbrontario@riseup.net>
 */
class V1XMLExporter extends XMLExporter
{
    /**
     * Get the DB that matches export version
     * 
     * @return JMDictDBInterface
     */
    protected function get_versioned_db()
    {
        return new JMDictDB($this->db_conn, $this->version_id);
    }
    
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
     * Output header
     * 
     * @return void
     */
    protected function output_header()
    {
        $header = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $header .= "<jmdict>\n";
        $this->file->write($header);
    }
    
    /**
     * Output footer
     * 
     * @return void
     */
    protected function output_footer()
    {
        $footer = "\n</jmdict>\n";
        $this->file->write($footer);
    }
    
    /**
     * Output entry
     * 
     * @param JMDictEntity $entry
     * 
     * @return void
     */
    protected function output_entry(JMDictEntity $entry)
    {
        $entry_xsd_string = "\n";
        $entry_xsd_string .= "  <entry entry_uid=\"$entry->entry_uid\" sequence_id=\"$entry->sequence_id\">\n";
        
        foreach($entry->kanjis as $kanji)
        {
            $entry_xsd_string .= "      <kanji kanji_uid=\"$kanji->kanji_uid\" >\n";
            $entry_xsd_string .= "          <binary form=\"raw\" >$kanji->binary_raw</binary>\n";
            
            if(!empty($kanji->binary_nfc))
            {
                $entry_xsd_string .= "          <binary form=\"nfc\" >$kanji->binary_nfc</binary>\n";
            }
            
            if(!empty($kanji->pris))
            {
                foreach($kanji->pris as $pri)
                {
                    $entry_xsd_string .= "          <pri>$pri[binary_raw]</pri>\n";
                }
            }
            
            if(!empty($kanji->infos))
            {
                foreach($kanji->infos as $info)
                {
                    $entry_xsd_string .= "          <info>$info[binary_raw]</info>\n";
                }
            }
            
            $entry_xsd_string .= "      </kanji>\n";
        }
        
        foreach($entry->readings as $reading)
        {
            $nokanji_attr = "";
            if(!empty($reading->b_no_kanji))
            {
                $nokanji_attr .= "no_kanji=\"true\"";
            }
            
            $entry_xsd_string .= "      <reading reading_uid=\"$reading->reading_uid\" $nokanji_attr>\n";
            $entry_xsd_string .= "          <binary form=\"raw\" >$reading->binary_raw</binary>\n";
            
            if(!empty($reading->binary_nfc))
            {
                $entry_xsd_string .= "          <binary form=\"nfc\" >$reading->binary_nfc</binary>\n";
            }
            
            if(!empty($reading->restrs))
            {
                foreach($reading->restrs as $restr)
                {
                    $entry_xsd_string .= "          <kanji_ref kanji_uid=\"$restr[kanji_uid]\">$restr[binary_raw]</kanji_ref>\n";
                }
            }
            
            if(!empty($reading->pris))
            {
                foreach($reading->pris as $pri)
                {
                    $entry_xsd_string .= "          <pri>$pri[binary_raw]</pri>\n";
                }
            }
            
            if(!empty($reading->infos))
            {
                foreach($reading->infos as $info)
                {
                    $entry_xsd_string .= "          <info>$info[binary_raw]</info>\n";
                }
            }
            
            $entry_xsd_string .= "      </reading>\n";
        }
        
        foreach($entry->senses as $sense)
        {
            $entry_xsd_string .= "      <sense sense_uid=\"$sense->sense_uid\">\n";
            
            if(!empty($sense->stagrs))
            {
                foreach($sense->stagrs as $stagr)
                {
                    $entry_xsd_string .= "          <reading_ref reading_uid=\"$stagr[reading_uid]\">$stagr[binary_raw]</reading_ref>\n";
                }
            }
            
            if(!empty($sense->stagks))
            {
                foreach($sense->stagks as $stagk)
                {
                    $entry_xsd_string .= "          <kanji_ref kanji_uid=\"$stagk[kanji_uid]\">$stagk[binary_raw]</kanji_ref>\n";
                }
            }
            
            if(!empty($sense->poses))
            {
                foreach($sense->poses as $pos)
                {
                    $entry_xsd_string .= "          <pos>$pos[binary_raw]</pos>\n";
                }
            }
            
            if(!empty($sense->glosses))
            {
                foreach($sense->glosses as $gloss)
                {
                    $gen_attr = "";
                    if(!empty($gloss['gend']))
                    {
                        $gen_attr .= " gend=\"$gloss[gend]\"";
                    }
                    
                    $lang_attr = "";
                    if(!empty($gloss['lang']))
                    {
                        $lang_attr .= " lang=\"$gloss[lang]\"";
                    }
                    
                    $entry_xsd_string .= "          <gloss{$gen_attr}{$lang_attr}>$gloss[binary_raw]</gloss>\n";
                }
            }
            
            if(!empty($sense->fields))
            {
                foreach($sense->fields as $field)
                {
                    $entry_xsd_string .= "          <field>$field[binary_raw]</field>\n";
                }
            }
            
            if(!empty($sense->miscs))
            {
                foreach($sense->miscs as $misc)
                {
                    $entry_xsd_string .= "          <misc>$misc[binary_raw]</misc>\n";
                }
            }
            
            if(!empty($sense->dials))
            {
                foreach($sense->dials as $dial)
                {
                    $entry_xsd_string .= "          <dial>$dial[binary_raw]</dial>\n";
                }
            }
            
            if(!empty($sense->infos))
            {
                foreach($sense->infos as $info)
                {
                    $entry_xsd_string .= "          <info>$info[binary_raw]</info>\n";
                }
            }
            
            if(!empty($sense->lsources))
            {
                foreach($sense->lsources as $lsource)
                {
                    $lang_attr = "";
                    if(!empty($lsource['lang']))
                    {
                        $lang_attr .= " lang=\"$lsource[lang]\"";
                    }
                    
                    $type_attr = "";
                    if(!empty($lsource['type']))
                    {
                        $type_attr .= " type=\"$lsource[type]\"";
                    }
                    
                    $wasei_attr = "";
                    if(!empty($lsource['wasei']))
                    {
                        $wasei_attr .= " wasei=\"$lsource[wasei]\"";
                    }
                    
                    $entry_xsd_string .= "          <lsource{$type_attr}{$lang_attr}{$wasei_attr}>$lsource[binary_raw]</lsource>\n";
                }
            }
            
            if(!empty($sense->xrefs))
            {
                foreach($sense->xrefs as $xref)
                {
                    $entry_xsd_string .= "          <xref>\n";
                    $entry_xsd_string .= "              <binary form=\"raw\">$xref[binary_raw]</binary>\n";
                    if(isset($xref['t_kanji_uid']))
                    {
                        $entry_xsd_string .= "              <kanji_ref kanji_uid=\"$xref[t_kanji_uid]\">$xref[t_kanji_binary_raw]</kanji_ref>\n";
                    }
                    if(isset($xref['t_reading_uid']))
                    {
                        $entry_xsd_string .= "              <reading_ref reading_uid=\"$xref[t_reading_uid]\">$xref[t_reading_binary_raw]</reading_ref>\n";
                    }
                    if(isset($xref['t_sense_uid']))
                    {
                        $entry_xsd_string .= "              <sense_ref sense_uid=\"$xref[t_sense_uid]\">$xref[t_sense_binary_raw]</sense_ref>\n";
                    }
                    $entry_xsd_string .= "          </xref>\n";
                }
            }
            
            if(!empty($sense->ants))
            {
                foreach($sense->ants as $ant)
                {
                    $entry_xsd_string .= "          <ant>\n";
                    $entry_xsd_string .= "              <binary form=\"raw\">$ant[binary_raw]</binary>\n";
                    if(isset($ant['t_kanji_uid']))
                    {
                        $entry_xsd_string .= "              <kanji_ref kanji_uid=\"$ant[t_kanji_uid]\">$ant[t_kanji_binary_raw]</kanji_ref>\n";
                    }
                    if(isset($ant['t_reading_uid']))
                    {
                        $entry_xsd_string .= "              <reading_ref reading_uid=\"$ant[t_reading_uid]\">$ant[t_reading_binary_raw]</reading_ref>\n";
                    }
                    if(isset($ant['t_sense_uid']))
                    {
                        $entry_xsd_string .= "              <sense_ref sense_uid=\"$ant[t_sense_uid]\">$ant[t_sense_binary_raw]</sense_ref>\n";
                    }
                    $entry_xsd_string .= "          </ant>\n";
                }
            }
            
            $entry_xsd_string .= "      </sense>\n";
        }
        
        $entry_xsd_string .= "  </entry>\n";
        
        $this->file->write($entry_xsd_string);
    }
}
