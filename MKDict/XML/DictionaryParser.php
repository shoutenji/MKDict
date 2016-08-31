<?php

namespace MKDict\XML;

use MKDict\FileResource\FileResource;
use MKDict\FileResource\TempFileResource;
use MKDict\DTD\DTD;
use MKDict\Database\JMDictDBInterface;
use MKDict\Exception\StaticBindingFailure;
use MKDict\Exception\LibXMLError;
use MKDict\Logger\Logger;

abstract class DictionaryParser
{
    protected $file, $dtd, $check_doc_name;
    protected $jmdb;
    protected $logger;
    protected $character_buffer, $longest_entity_name, $entity_regexp_pattern;
    protected $parser;
    protected $sequence_ids;
    protected $tmp_file;
    protected $entry, $reading, $kanji, $sense;
    
    public function get_parser_passes()
    {
        return array("parser_pass_1");
    }
    
    public function __construct(FileResource $file, DTD $dtd, JMDictDBInterface $jmdb, Logger $logger)
    {
        global $options;
        
        if($options['debug_version'] && (!property_exists($this, "ENTITY_ID") || empty(static::$ENTITY_ID)))
        {
            throw new StaticBindingFailure("ENTITY_ID var not defined or empty in inherited class \"".get_called_class()."\"");
        }
        
        $this->jmdb = $jmdb;
        $this->file = $file;
        $this->dtd = $dtd;
        $this->version_id = $this->dtd->version_id;
        $this->logger = $logger;
        $this->character_buffer = "";
        $this->tmp_file = new TempFileResource();
        $this->tmp_file->open();
        $this->sequence_ids = array();

        $this->parser = xml_parser_create('UTF-8');
        xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($this->parser, XML_OPTION_SKIP_WHITE, 1);
        xml_set_element_handler($this->parser, array($this,"start_element_handler"), array($this,"end_element_handler"));
        xml_set_character_data_handler($this->parser, array($this,"character_data_handler"));

        //flatten the elements array of the dtd
        $elements = array();
        foreach($this->dtd->elements as $element)
        {
            $elements[$element['name']] = 
                (
                    is_array($element['parsed_contentspec']) && count($element['parsed_contentspec']) == 1
                    &&
                    $element['parsed_contentspec'][0] === "#PCDATA"
                )?
                $element['parsed_contentspec'][0] : $element['parsed_contentspec'];
        }
        $this->dtd->elements = $elements;
        unset($elements);

        //we need the strlen of the longest entity name, see below
        $entity_names = array_column($this->dtd->entities, 'name');
        array_walk($entity_names, function($value, $index){
            if(strlen($value) > strlen($this->longest_entity_name))
            {
                $this->longest_entity_name = $value;
            }
        });
        unset($entity_names);

        $this->entity_regexp_pattern = "/&(";
        foreach(array_column($this->dtd->entities, 'name') as $entity)
        {
            $entity = preg_quote("$entity");
            $this->entity_regexp_pattern .= "(?:$entity)|";
        }
        $this->entity_regexp_pattern = rtrim($this->entity_regexp_pattern, "|");
        $this->entity_regexp_pattern .= ");/ixsm";
    }
    
    protected function validate_int($data)
    {
        if(is_string($data) && $data === "")
        {
            $this->logger->invalid_int($data, $this->entry);
            return false;
        }
        return intval(trim($data));
    }


    //TODO sanatize for SQL injection
    //todo right now validate_int() does not log an errors but this function does. need consistent behaviour
    protected function validate_string($data, $can_be_empty = false)
    {
        global $config;
        
        $data = strval($data); 
        
        if(strlen($data) > $config['string_max_byte_len'])
        {
            $this->logger->invalid_string($data, $this->entry, "String exceeds maximum length.");
            return false;
        }
        
        $data = trim($data);
        if(!$can_be_empty && empty($data))
        {
            $this->logger->invalid_string($data, $this->entry, "Empty string.");
            return false;
        }
        
        return $data;
    }
    
    final public function parser_pass_1()
    {
        global $config;
        
        $result = false;
        $in_cdata_section = false; //flag to indicate whether or not our position in the text stream resides in a cdata section
        while(!$this->file->feof())
        {
            $next_line = $this->file->read($config['parser_stream_read_size']);

            //the only reason we must process this text stream beyond a simple piece-wise read is because we need to replace entities, and adding
            //further complication, the reading operations don't respect token boundaries

            //the next few lines of code make sure that our read operation didnt cleave a cdata token
            //note: cdata sections cannot nest
            $line_len = strlen($next_line);
            $cdata_start_token = "<![CDATA[";
            $cdata_end_token = "]]>";
            $cdata_token = "";
            $cdata_token_split = false;
            $entity_token_split = false;
            
            //check if the tail of the text chuck just read contains a character from either the start or the end cdata tokens
            //QWERTY
            //*********QWE
            //123456789ABC  <-character positions in hex
            //strrpos(Q) will yield 10
            //strlen(QWERTY) will yield 6
            //however, we don't touch entities if they reside in a cdata section
            if($line_len - strlen($this->longest_entity_name) < strrpos($next_line, "&"))
            {
                $index = strrpos($next_line, "&");
                if(false == strpos(substr($next_line, $index),";"))
                {
                    $entity_token_split = true;
                }
            }
            else if($line_len - strlen($cdata_start_token) < strrpos($next_line, $cdata_start_token[0]))
            {
                $cdata_token_split = true;
                $cdata_token = $cdata_start_token;
                $index = strrpos($next_line, $cdata_start_token[0]);
            }
            else if($line_len - strlen($cdata_end_token) < strrpos($next_line, $cdata_end_token[0]))
            {
                $cdata_token_split = true;
                $cdata_token = $cdata_end_token;
                $index = strrpos($next_line, $cdata_end_token[0]);
            }
            
            //if we split a token, then read in as many characters as necessary to complete the rest of that full token
            if($cdata_token_split)
            {
                $count = 0;
                $test = $cdata_token[$count];
                while($test === strtoupper($next_line[$index]))
                {
                    $count++; $index++;
                    if($count + 1 > strlen($cdata_token))
                    {
                        break;
                    }
                    if(!isset($next_line[$index]))
                    {
                        $next_line .= $this->file->read(1);
                    }
                    $test = $cdata_token[$count];
                }
            }
            else if($entity_token_split)
            {
                $count = 0;
                while($count < strlen($this->longest_entity_name))
                {
                   $next_char = $this->file->read(1);
                   $next_line .= $next_char;
                   if($next_char === ";")
                   {
                       break;
                   }
                   $count++;
                }
            }
            
            //having now a clean read operation of the text, its safe to resume parsing, but we still must work around cdata sections
            //but still must work around cdata sections
            $next_line_splits = preg_split("/ ( <!\[CDATA\[(?:.*)\]\]> ) /ixsm", $next_line, -1, \PREG_SPLIT_NO_EMPTY | \PREG_SPLIT_DELIM_CAPTURE | \PREG_SPLIT_OFFSET_CAPTURE);
            if($in_cdata_section)
            {
                $first_split = $next_line_splits[0];
                $cdata_closer = strpos($first_split[0], "]]>");
                $cdata_opener = strpos($first_split[0], "<![CDATA[");
                
                if(false !== $cdata_closer)
                {
                    array_shift($results);
                    if(false !== $cdata_opener)
                    {
                        $in_cdata_section = true;
                        array_unshift($next_line_splits, array(
                            0 => substr($first_split[0], 0, $cdata_closer+3),
                            'replace' => false,
                        ));
                        array_push($next_line_splits, array(
                            0 => substr($first_split[0], $cdata_closer+3, $cdata_opener - $cdata_closer - 3),
                            'replace' => true,
                        ));
                        array_push($next_line_splits, array(
                            0 => substr($first_split[0], $cdata_opener),
                            'replace' => false,
                        ));
                    }
                    else
                    {
                        $in_cdata_section = false;
                        array_unshift($next_line_splits, array(
                            0 => substr($first_split[0], 0, $cdata_closer+3),
                            'replace' => false,
                        ));
                        array_push($next_line_splits, array(
                            0 => substr($first_split[0], $cdata_closer+3),
                            'replace' => true,
                        ));
                    }
                }
                else if(false === $cdata_opener)
                {
                    $next_line_splits[0]['replace'] = 0;
                }
            }
            else
            {
                $last_split = $next_line_splits[count($next_line_splits) - 1];
                $cdata_closer = strpos($last_split[0], "]]>");
                $cdata_opener = strpos($last_split[0], "<![CDATA[");
                if($cdata_opener > $cdata_closer)
                {
                    $in_cdata_section = true;
                    array_pop($next_line_splits);
                    array_push($next_line_splits, array(
                        0 => substr($last_split[0], 0, $cdata_opener),
                        'replace'   => true,
                    ));
                    array_push($next_line_splits, array(
                        0 => substr($last_split[0], $cdata_opener),
                        'replace' => false,
                    ));
                }
                else if(count($next_line_splits) == 1)
                {
                    $next_line_splits[0]['replace'] = 1;
                }
            }
            
            foreach($next_line_splits as &$next_line_split)
            {
                if(isset($next_line_split['replace']))
                {
                    if($next_line_split['replace'])
                    {
                        $next_line_split[0] = preg_replace($this->entity_regexp_pattern, static::$ENTITY_ID ."\\1".static::$ENTITY_ID , $next_line_split[0]);
                    }
                }
                else if(substr($next_line_split[0], 0, strlen($cdata_start_token)) == $cdata_start_token && substr($next_line_split[0], -strlen($cdata_end_token)) == $cdata_end_token)
                {
                    //don't replace here
                }
                else
                {
                    $next_line_split[0] = preg_replace($this->entity_regexp_pattern, static::$ENTITY_ID ."\\1".static::$ENTITY_ID , $next_line_split[0]);
                }
            }
            unset($next_line_split);
            
            $next_line = implode("",array_column($next_line_splits, 0));
            
            //echo "**************************************\n";
            //echo "$next_line\n";
            //echo "**************************************\n";
            $result = xml_parse($this->parser, $next_line, $this->file->feof());
            if($result === 0)
            {
                $errors = libxml_get_errors();
                foreach($errors as $error)
                {
                    $level = $error->level;
                    if($level === \LIBXML_ERR_WARNING)
                    {
                        $this->logger->libxml_warning($error);
                    }
                    else if($level == \LIBXML_ERR_ERROR || $level === \LIBXML_ERR_FATAL)
                    {
                        throw new LibXMLError($error);
                    }
                }
            }
        }
        return $result;
    }
    
    
    protected function replace_entity_tags($text, $strip_tags_completely = false)
    {
        $replacement = $strip_tags_completely ? "\\1" : "&\\1;";
        return preg_replace("/ ".static::$ENTITY_ID." (.*) ".static::$ENTITY_ID." /xu", $replacement, $text);
    }
}

