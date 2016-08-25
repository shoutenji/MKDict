<?php

namespace MKDict\Installer;

use MKDict\Command\CommandArgs;
use MKDict\Logger\InstallLogger;
use MKDict\Database\DB;
use MKDict\FileResource\FileInfo;
use MKDict\FileResource\Url;
use MKDict\FileResource\PlainTextFileResource;
use MKDict\FileResource\CSVFileResource;
use MKDict\FileResource\PHPFileResource;
use MKDict\Unicode\Unicode;

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
            $this->generate_utf8_data_files();
        }
        
        if($this->options['utf_tests'])
        {
            $this->test_utf8_data_files();
        }
    }
    
    
    protected function test_utf8_data_files()
    {
        
    }
    
    //todo move these unicode related functions to another class
    protected function download_unicode_data_files()
    {
        global $config;
        
        //download unicode data files
        foreach($config['unicode_data_files'] as $file_tag => $file_info)
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
            $local_file_info = new FileInfo($file_info['name'], $config['data_dir']);
            $local_file_info->set_mode("w");
            $local_data_file = new PlainTextFileResource($local_file_info);
            $local_data_file->open();
            
            //download the remote file into the local file
            $local_data_file->download_from($remote_data_file);
        }
    }
    
    //todo this is looking pretty ugly and repetitive here. the processing in this function needs be moved to the CSVIterator or CSVFileResource class
    //and need to take better advantage of the file format
    //the Install class should not be in charge of data process, but only the managment of (other classes that do) the processing
    //todo truncate the text following the comment character
    //FYI The file format for the various unicode data files can be found here http://www.unicode.org/reports/tr44/#Format_Conventions
    protected function generate_utf8_data_files()
    {
        global $config;
        
        //CaseFolding.txt
        $case_folding_data_finfo = new FileInfo($config['unicode_data_files']['CaseFolding']['name'], $config['data_dir']);
        $case_folding_data_finfo->set_mode("r");
        $case_folding_data_finfo->set_option(FileInfo::OPTION_IGNORE_BLANK_LINES, true);
        $case_folding_data_finfo->set_option(FileInfo::OPTION_COMMENT_CHAR, "#");
        $case_folding_data_finfo->set_option(FileInfo::OPTION_VALUE_DELIMITER, ";");
        $case_folding_data_file = new CSVFileResource($case_folding_data_finfo);
        $case_folding_data_file->open();
        $case_folding_data_line_iterator = $case_folding_data_file->getIterator();
        
        $case_mapping_finfo = new FileInfo("case_mapping_f.php", $config['data_dir']);
        $case_mapping_finfo->set_mode("w");
        $case_mapping_file = new PHPFileResource($case_mapping_finfo);
        $case_mapping_file->open();
        $case_mapping_file->header("<?php \n \$GLOBALS['case_map'] = array(");
        $case_mapping_file->footer(");");
        
        foreach($case_folding_data_line_iterator as $case_folding_data_line)
        {
            //<code>; <status>; <mapping>; # <name>
            //example:
            //0041; C; 0061; # LATIN CAPITAL LETTER A
            //00DF; F; 0073 0073; # LATIN SMALL LETTER SHARP S
            //we are interested in status values of C or F
            //note the <mapping> may or may not be a pair of codepoints
            $case_folding_data = explode(";", $case_folding_data_line);
            
            if(count($case_folding_data) < 3) //just in case the iterator missed filtering a line
                continue;
            
            $code_point = trim($case_folding_data[0]);
            $status = strtoupper(trim($case_folding_data[1]));
            $mapping_value = trim($case_folding_data[2]);
            
            if(strspn($status, "CF") === 1)
            {
                $mapping_points = explode(" ", $mapping_value);
                if(count($mapping_points) > 1)
                {
                    $mapping_utf8_chars = "";
                    foreach($mapping_points as $mapping_point)
                    {
                        $mapping_utf8_chars .= Unicode::utf_to_utf8(trim($mapping_point),"char");
                    }
                    $upper_case_char = PHPFileResource::sanatize_string_literal(Unicode::utf_to_utf8($code_point,"char"));
                    $lower_case_char = PHPFileResource::sanatize_string_literal($mapping_utf8_chars);
                }
                else
                {
                    $upper_case_char = PHPFileResource::sanatize_string_literal(Unicode::utf_to_utf8($code_point, "char"));
                    $lower_case_char = PHPFileResource::sanatize_string_literal(Unicode::utf_to_utf8($mapping_value,"char"));
                }
                $case_mapping_file->body_add("\"$upper_case_char\"=>\"$lower_case_char\",\n");
            }
        }
        $case_mapping_file->create_file();
        $case_mapping_file->close();
        unset($case_folding_data_line_iterator);
        
        //DerivedNormalizationProps.txt
        $normalization_props_data_finfo = new FileInfo($config['unicode_data_files']['DerivedNormalizationProps']['name'], $config['data_dir']);
        $normalization_props_data_finfo->set_mode("r");
        $normalization_props_data_finfo->set_option(FileInfo::OPTION_IGNORE_BLANK_LINES, true);
        $normalization_props_data_finfo->set_option(FileInfo::OPTION_COMMENT_CHAR, "#");
        $normalization_props_data_finfo->set_option(FileInfo::OPTION_VALUE_DELIMITER, ";");
        $normalization_props_data_file = new CSVFileResource($normalization_props_data_finfo);
        $normalization_props_data_file->open();
        $normalization_props_data_iterator = $normalization_props_data_file->getIterator();
        
        $nfc_qc_finfo = new FileInfo("nfc_qc.php", $config['data_dir']);
        $nfc_qc_finfo->set_mode("w");
        $nfc_qc_file = new PHPFileResource($nfc_qc_finfo);
        $nfc_qc_file->open();
        $nfc_qc_file->header("<?php \n \$GLOBALS['nfc_qc'] = array(");
        $nfc_qc_file->footer(");");
        
        $nfkc_qc_finfo = new FileInfo("nfkc_qc.php", $config['data_dir']);
        $nfkc_qc_finfo->set_mode("w");
        $nfkc_qc_file = new PHPFileResource($nfkc_qc_finfo);
        $nfkc_qc_file->open();
        $nfkc_qc_file->header("<?php \n \$GLOBALS['nfkc_qc'] = array(");
        $nfkc_qc_file->footer(");");
        
        $composition_exclusions = array();
        
        foreach($normalization_props_data_iterator as $normalization_props_data_line)
        {
            //NFC_QC
            //0343..0344    ; NFC_QC; N # Mn   [2] COMBINING GREEK KORONIS..COMBINING GREEK DIALYTIKA TONOS
            //0374          ; NFC_QC; N # Lm       GREEK NUMERAL SIGN
            //NFKC_QC
            //00B2..00B3    ; NFKC_QC; N # No   [2] SUPERSCRIPT TWO..SUPERSCRIPT THREE
            //00B4          ; NFKC_QC; N # Sk       ACUTE ACCENT
            //0343..0344    ; Full_Composition_Exclusion # Mn   [2] COMBINING GREEK KORONIS..COMBINING GREEK DIALYTIKA TONOS
            //0374          ; Full_Composition_Exclusion # Lm       GREEK NUMERAL SIGN
            $normalization_props_data = explode(';', $normalization_props_data_line);
            
            if(count($normalization_props_data) < 2)
            {
                continue;
            }
            
            $code_point = trim($normalization_props_data[0]);
            $status = strtoupper(trim($normalization_props_data[1]));
            
            if(false !== strpos($status, "#"))
            {
                $status = trim(strstr($status, "#", true));
            }
            
            if(!in_array($status, ["NFC_QC", "NFKC_QC", "Full_Composition_Exclusion"]))
            {
                continue;
            }
            
            $code_point_range = array();
            
            if(false !== strpos($code_point, ".."))
            {
                $code_points = explode("..", $code_point);
                $range_start_point = hexdec(trim($code_points[0]));
                $range_end_point = hexdec(trim($code_points[1]));
                
                foreach(range($range_start_point, $range_end_point) as $code_point_ranged)
                {
                    $code_point_range[] = $code_point_ranged;
                }
            }
            else
            {
                //a code point scalar, but store it as the sole element in an array anyways
                $code_point_range[] = $code_point;
            }
            
            foreach($code_point_range as $code_point_ranged)
            {
                $utf8_character = Unicode::utf_to_utf8($code_point_ranged, "char");
                switch($status)
                {
                    case "NFC_QC":
                        $nfc_qc_file->body_add("\"".PHPFileResource::sanatize_string_literal($utf8_character)."\",\n");
                        break;

                    case "NFKC_QC":
                        $nfkc_qc_file->body_add("\"".PHPFileResource::sanatize_string_literal($utf8_character)."\",\n");
                        break;

                    case "Full_Composition_Exclusion":
                        $composition_exclusions[] = $utf8_character;
                        break;

                    default:
                        break;
                }
            }
        }
        unset($normalization_props_data_iterator);
        
        $nfc_qc_file->create_file();
        $nfc_qc_file->close();
        
        $nfkc_qc_file->create_file();
        $nfkc_qc_file->close();

        //HangulSyllableType.txt
        $hangul_syllable_type_data_finfo = new FileInfo($config['unicode_data_files']['HangulSyllableType']['name'], $config['data_dir']);
        $hangul_syllable_type_data_finfo->set_mode("r");
        $hangul_syllable_type_data_finfo->set_option(FileInfo::OPTION_IGNORE_BLANK_LINES, true);
        $hangul_syllable_type_data_finfo->set_option(FileInfo::OPTION_COMMENT_CHAR, "#");
        $hangul_syllable_type_data_finfo->set_option(FileInfo::OPTION_VALUE_DELIMITER, ";");
        $hangul_syllable_type_data_file = new CSVFileResource($hangul_syllable_type_data_finfo);
        $hangul_syllable_type_data_file->open();
        $hangul_syllable_type_data_iterator = $hangul_syllable_type_data_file->getIterator();
        
        $hangul_syllable_LV = array();
        $hangul_syllable_LVT = array();
        
        foreach($hangul_syllable_type_data_iterator as $hangul_syllable_type_data_line)
        {
            $hangul_syllable_type_data = explode(";", $hangul_syllable_type_data_line);
            
            if(count($hangul_syllable_type_data) < 1)
            {
                continue;
            }
            
            $code_point = trim($hangul_syllable_type_data[0]);
            $status = strtoupper(trim($hangul_syllable_type_data[1]));
            
            if(false !== strpos($status, "#"))
            {
                $status = trim(strstr($status, "#", true));
            }
            
            if(!in_array($status, ["LV", "LVT"]))
            {
                continue;
            }
            
            $code_point_range = array();
            
            if(false !== strpos($code_point, ".."))
            {
                $code_points = explode("..", $code_point);
                $range_start_point = hexdec(trim($code_points[0]));
                $range_end_point = hexdec(trim($code_points[1]));
                
                foreach(range($range_start_point, $range_end_point) as $code_point_ranged)
                {
                    $code_point_range[] = $code_point_ranged;
                }
            }
            else
            {
                //a code point scalar, but store it as the sole element in an array anyways
                $code_point_range[] = $code_point;
            }
            
            foreach($code_point_range as $code_point_ranged)
            {
                $utf8_character = Unicode::utf_to_utf8($code_point_ranged, "char");
                switch($status)
                {
                    case "LV":
                        $hangul_syllable_LV[$utf8_character] = Unicode::decompose_hangul($code_point_ranged);
                        break;
                    
                    case "LVT":
                        $hangul_syllable_LVT[$utf8_character] = Unicode::decompose_hangul($code_point_ranged);
                        break;
                    
                    default:
                        break;
                }
            }
        }
        unset($hangul_syllable_type_data_iterator);
        
        //UnicodeData.txt
        $unicodeData_data_finfo = new FileInfo($config['unicode_data_files']['UnicodeData']['name'], $config['data_dir']);
        $unicodeData_data_finfo->set_mode("r");
        $unicodeData_data_finfo->set_option(FileInfo::OPTION_IGNORE_BLANK_LINES, true);
        $unicodeData_data_finfo->set_option(FileInfo::OPTION_COMMENT_CHAR, "#");
        $unicodeData_data_finfo->set_option(FileInfo::OPTION_VALUE_DELIMITER, ";");
        $unicodeData_data_file = new CSVFileResource($unicodeData_data_finfo);
        $unicodeData_data_file->open();
        $unicodeData_data_iterator = $unicodeData_data_file->getIterator();
        
        $canonical_decompositions = array();
        $compatibility_decompositions = array();
        $ccc_class_map = array();
        
        foreach($unicodeData_data_iterator as $unicodeData_data_line)
        {
            //the UnicodeData.txt format is given here http://www.unicode.org/reports/tr44/#UnicodeData.txt
            //0000;<control>;Cc;0;BN;;;;;N;NULL;;;;
            //0001;<control>;Cc;0;BN;;;;;N;START OF HEADING;;;;
            //2105;CARE OF;So;0;ON;<compat> 0063 002F 006F;;;;N;;;;;
            //2106;CADA UNA;So;0;ON;<compat> 0063 002F 0075;;;;N;;;;;
            $unicodeData_data = explode(";", $unicodeData_data_line);
            
            $utf8_character = Unicode::utf_to_utf8(trim($unicodeData_data[0]), "char");
            $decomposition_type_property = trim($unicodeData_data[5]);
            $ccc_class_property = intval(trim($unicodeData_data[3]));
            
            if(!empty($decomposition_type_property))
            {
                //note: the field we are looking at as the following possible values
                //<compat> 0063 002F 006F
                //0063 002F 006F
                
                $is_compatibility_type = substr($decomposition_type_property, 0, 1) === "<";
                
                if($is_compatibility_type)
                {
                    //get the numbers following the <compat> part
                    $decomposition_values_string = trim(strrchr($decomposition_type_property, ">"), "> \t\n\r\0\x0B");
                    $decomposition_values = explode(" ", $decomposition_values_string);
                    $decomposition_value_flat = "";
                    foreach($decomposition_values as $decomposition_value)
                    {
                        //$compatibility_decompositions[$utf8_character][] = Unicode::utf_to_utf8($decomposition_value, "char");
                        $decomposition_value_flat .= Unicode::utf_to_utf8($decomposition_value, "char");
                    }
                    $compatibility_decompositions[$utf8_character] = $decomposition_value_flat;
                }
                else
                {
                    $decomposition_values = explode(" ", $decomposition_type_property);
                    $decomposition_value_flat = "";
                    foreach($decomposition_values as $decomposition_value)
                    {
                        //$canonical_decompositions[$utf8_character][] = Unicode::utf_to_utf8($decomposition_value, "char");
                        $decomposition_value_flat .= Unicode::utf_to_utf8($decomposition_value, "char");
                    }
                    $canonical_decompositions[$utf8_character] = $decomposition_value_flat;
                }
            }
            
            if($ccc_class_property > 0)
            {
                $ccc_class_map[$utf8_character] = $ccc_class_property;
            }
        }
        unset($unicodeData_data_iterator);
        
        $primary_composites = array_merge($hangul_syllable_LV, $hangul_syllable_LVT, $canonical_decompositions);
        $primary_composites = array_diff_key($primary_composites, array_flip($composition_exclusions));
        $primary_composites = array_flip($primary_composites);
        
        $primary_composites_finfo = new FileInfo("primary_composites.php", $config['data_dir']);
        $primary_composites_finfo->set_mode("w");
        $primary_composites_file = new PHPFileResource($primary_composites_finfo);
        $primary_composites_file->open();
        $primary_composites_file->header("<?php \n \$GLOBALS['primary_composites'] = array(");
        $primary_composites_file->footer(");");
        
        while(list($utf8_char, $utf8_decomp_chars) = each($primary_composites))
        {
            $utf8_char = PHPFileResource::sanatize_string_literal($utf8_char);
            $utf8_decomp_chars = PHPFileResource::sanatize_string_literal($utf8_decomp_chars);
            $primary_composites_file->body_add("\"$utf8_char\" => \"$utf8_decomp_chars\",\n");
        }
        $primary_composites_file->create_file();
        $primary_composites_file->close();
    
        $ccc_class_map_finfo = new FileInfo("ccc_class_map.php", $config['data_dir']);
        $ccc_class_map_finfo->set_mode("w");
        $ccc_class_map_file = new PHPFileResource($ccc_class_map_finfo);
        $ccc_class_map_file->open();
        $ccc_class_map_file->header("<?php \n \$GLOBALS['ccc_class'] = array(");
        $ccc_class_map_file->footer(");");
        
        while(list($utf8_char, $ccc_value) = each($ccc_class_map))
        {
            $utf8_char = PHPFileResource::sanatize_string_literal($utf8_char);
            $ccc_class_map_file->body_add("\"$utf8_char\" => $ccc_value,\n");
        }
        $ccc_class_map_file->create_file();
        $ccc_class_map_file->close();
    
        
        $canonical_decomposition_finfo = new FileInfo("canonical_decompositions.php", $config['data_dir']);
        $canonical_decomposition_finfo->set_mode("w");
        $canonical_decomposition_file = new PHPFileResource($canonical_decomposition_finfo);
        $canonical_decomposition_file->open();
        $canonical_decomposition_file->header("<?php \n \$GLOBALS['canon_decomp'] = array(");
        $canonical_decomposition_file->footer(");");
        
        while(list($decomposition_char, $decomposition_value) = each($canonical_decompositions))
        {
            $decomposition_char = PHPFileResource::sanatize_string_literal($decomposition_char);
            $decomposition_value = PHPFileResource::sanatize_string_literal($decomposition_value);
            $canonical_decomposition_file->body_add("\"$decomposition_char\" => \"$decomposition_value\",\n");
        }
        $canonical_decomposition_file->create_file();
        $canonical_decomposition_file->close();
    
        
        $comaptibility_decompositions_finfo = new FileInfo("compatibility_decompositions.php", $config['data_dir']);
        $comaptibility_decompositions_finfo->set_mode("w");
        $comaptibility_decompositions_file = new PHPFileResource($comaptibility_decompositions_finfo);
        $comaptibility_decompositions_file->open();
        $comaptibility_decompositions_file->header("<?php \n \$GLOBALS['compat_decomp'] = array(");
        $comaptibility_decompositions_file->footer(");");
        
        while(list($decomposition_char, $decomposition_values) = each($compatibility_decompositions))
        {
            $decomposition_char = PHPFileResource::sanatize_string_literal($decomposition_char);
            $decomposition_value = PHPFileResource::sanatize_string_literal($decomposition_value);
            $comaptibility_decompositions_file->body_add("\"$decomposition_char\" => \"$decomposition_value\",\n");
        }
        $comaptibility_decompositions_file->create_file();
        $comaptibility_decompositions_file->close();
    }
}
