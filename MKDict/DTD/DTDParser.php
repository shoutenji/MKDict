<?php

namespace MKDict\DTD;

use MKDict\FileResource\FileResource;
use MKDict\DTD\Exception\DTDError;

class DTDParser
{
    private $document_name;
    private $elements;
    private $attributes;
    private $entities;
    
    protected $file;
    
    public function __construct(FileResource $file)
    {
        $this->file = $file;
    }
    
    public function get_document_name()
    {
        return $this->document_name;
    }
    
    public function get_elements()
    {
        return $this->elements;
    }
    
    public function get_attributes()
    {
        return $this->attributes;
    }
    
    public function get_entities()
    {
        return $this->entities;
    }
    
   public function canonicalize()
    {
        global $config;
        
        //whitespace is treated quite forgivingly, for example we accept a newline where a space would otherwise be expected
        //all the regexp matching is case insensitive
        //for element contentspecs of the form '( #PCDATA (| name)* )*' the outermost * quantifier is discarded for the purpose of canonicalization
        //when using these make sure the sequence $something[ does not appear anywhere in the strings below otherwise php will begin to parse $something[ as if it were indexing and a bug will ensue!
        //forward slash / must be escaped in these strings, ie \/, as the forward slash is used as the regex delimiter
        //the common regex flags used further below are xium
        //NOTE the most common bug here is the improper use of $whitespace. remember the x flag is used so any anticipated whitespace must be explicitly included
        //NOTE patterns need to be enclosed in the non-capturing subpattern (?: ) because proper nesting requires each string component to be atomic (otherwise you get unintented squences of '(?:' and ')')
        $whitespace = "(?: \x{00} | \x{20} | \x{09} | \x{0D} | \x{0A} )";
        $squote = "\'"; 
        $dquote = "\"";
        $name_start_char =  "(?: : | [A-Z] | _  | [\xC3\x80-\xC3\x96] | [\xC3\x98-\xC3\xB6] | [\xC3\xB8-\xCB\xBF] | [\xCD\xB0-\xCD\xBD] | [\xCD\xBF-\xE1\xBF\xBF] | [\xE2\x80\x8C-\xE2\x80\x8D] | [\xE2\x81\xB0-\xE2\x86\x8F] | [\xE2\xB0\x80-\xE2\xBF\xAF] | [\xE3\x80\x81-\xED\x9F\xBF] | [\xEF\xA4\x80-\xEF\xB7\x8F] | [\xEF\xB7\xB0-\xEF\xBF\xBD] | [\xF0\x90\x80\x80-\xF3\xAF\xBF\xBF] )";
        $name_char = "(?: $name_start_char | - | \. | [0-9] | \xC2\xB7 | [\xCC\x80-\xCD\xAF] | [\xE2\x80\xBF-\xE2\x81\x80] )";
        $name_chars = "(?: $name_start_char | $name_char )";
        $name = "(?: $name_start_char (?: $name_char )* )";
        $names = "(?: $name (?: $whitespace+ $name )* )";
        $nmtoken = "(?: $name_char+ )";
        $nmtokens = "(?: $nmtoken (?: $whitespace+ $nmtoken )* )";
        $pcdata = "\#PCDATA";

        $charater_reference = "(?: (?: \&\#[0-9]+; ) | (?: \&\#x[0-9A-F]+; ) )";
        $parameter_entity_reference = "%$name;";
        $entity_reference = "&$name;";
        $reference = "(?: $entity_reference | $charater_reference )";
        $entity_value = "(?: (?:$squote(?:[^%&$squote]+ | $parameter_entity_reference | $reference )*$squote) | (?:$dquote(?:[^%&$dquote]+ | $parameter_entity_reference | $reference)*$dquote) )";

        $pubidChar = "(?: \x{20} | \x{0D} | \x{0A} | [[:alnum:]] )";
        $pubidLiteral = "(?: (?:$squote(?:$pubidChar|[$dquote\-\(\)\.\$\+\*\?,:=;@\#!_%\/])*$squote) | (?:$dquote(?:$pubidChar|[$dquote\-\(\)\.\$\+\*\?,:=;@\#!_%\/])*$dquote) )";
        $systemLiteral = "(?: (?:$dquote [^$dquote]*$dquote) | (?:$squote [^$squote]*$squote) )";
        $external_id = "(?: (?: SYSTEM $whitespace+ $systemLiteral ) | (?: PUBLIC $whitespace+ $pubidLiteral $whitespace+ $systemLiteral ) )";
        $parameter_entity_definition = "(?: $entity_value | $external_id )";
        $ndata_declaration = "(?: $whitespace+ NDATA $whitespace+ $name)";
        $external_entity_definition = "(?: $external_id  $ndata_declaration?)";
        $unpermitted_element_definition = "(?: (?: \( (?: $whitespace* $pcdata  $whitespace*)? (?: $whitespace* \|? $whitespace* $parameter_entity_reference $whitespace* )+ $whitespace*  \) \* ) | 
                                            (?: $whitespace* $parameter_entity_reference $whitespace* ) )";
        $attribute_value = "(?: (?: $dquote (?: [^<&$dquote] | $reference)* $dquote ) | (?: $squote (?: [^<&$squote] | $reference )* $squote) )";
        $permitted_entity_definition = $attribute_value;
        $attribute_default_declaration = "(?: \#REQUIRED | \#IMPLIED | (?: (?: \#FIXED $whitespace+ )? $attribute_value ) )";
        $attribute_type = "(?: CDATA | ID | IDREFS | NMTOKEN | NMTOKENS | (?: \( $whitespace* \|? $nmtoken  $whitespace* \) )* )";

        $parameter_entity_declaration = "<!ENTITY  $whitespace+  %  $whitespace+  $name  $whitespace+  $parameter_entity_definition  $whitespace*  >";
        $external_entity_declaration = "<!ENTITY $whitespace+  $name  $whitespace+  $external_entity_definition  $whitespace*  >";
        $unpermitted_element_declarations = "<!ELEMENT $whitespace+  $name  $whitespace+  $unpermitted_element_definition  $whitespace*  >";
        $conditional_declarations = "<!\[ $whitespace+ (?: IGNORE | INCLUDE ) $whitespace+ \[ .* \]\]>";
        $notation_declaration = "<!NOTATION $whitespace+ $name $whitespace+ (?: $external_id | (?: PUBLIC $whitespace+ $pubidLiteral $whitespace? ) ) $whitespace* >";
        $unpermitted_attribute_declarations = "<!ATTLIST $whitespace+ $name $whitespace+ (?: $name $whitespace+ (?: ENTITY | ENTITIES ) $whitespace+ $attribute_default_declaration )* $whitespace* >";

        $children = "\( .* \)";
        $mixed = "(?: \( $whitespace* $pcdata (?: $whitespace* \| $whitespace* $name )* $whitespace* \)\* | \( $whitespace* $pcdata $whitespace* \) )";
        $permitted_element_definition = "(?: EMPTY | ANY | $mixed | $children )";
        $permitted_element_declarations = "<!ELEMENT $whitespace+  ($name)  $whitespace+  ($permitted_element_definition)  $whitespace*  >";
        $permitted_attribute_declarations = "<!ATTLIST $whitespace+ ($name) $whitespace+ (?: ($name) $whitespace+ ($attribute_type) $whitespace+ ($attribute_default_declaration) )* $whitespace* >";
        $permitted_entity_declarations = "<!ENTITY $whitespace+ ($name) $whitespace+ ($permitted_entity_definition) $whitespace* >";
        
        $dtd = array();
        
        //extract and clean up the xml dec
        $line = "";
        while(!$this->file->feof())
        {
            $line .= $this->file->read(512);
            $line = ltrim($line);
            $line = str_replace(array("\r\n","\r"), "\n", $line);
            preg_match("/^<\?xml $whitespace+ (.*) $whitespace* \?>/iuxms", $line, $matches);
            if(isset($matches[0]))
            {
                $xml_dec_len = strlen($matches[0]);
                $xml_dec = $matches[1];
                //clean up the xml_dec
                $xml_dec_clean = "<?xml ";
                preg_match_all("/ ([\w]*) $whitespace* = $whitespace* [\'\"] ([^\'\"]*) [\'\"] /iuxms", $xml_dec, $attrs, \PREG_SET_ORDER);
                foreach($attrs as $attr)
                {
                    //the declared xml encoding can be grabbed here if needed
                    $xml_dec_clean .= $attr[1] . "=\"" .$attr[2] . "\" "; 
                }
                $xml_dec_clean .= "?>";
                break;
            }
        }
        
        //the xml declaration is optional
        if(!isset($xml_dec_len))
        {
            $xml_dec = "";
            $xml_dec_len = -1;
        }
        $dtd['xmldec'] = $xml_dec;
        
        //mk_gzseek($gzhandle, $xml_dec_len + 1, SEEK_SET, __FILE__, __LINE__);
        $this->file->seek($xml_dec_len + 1, \SEEK_SET);
        
        //capture the dtd raw
        $line = "";
        while(!$this->file->feof())
        {
            $line .= $this->file->read(512);
            $line = str_replace(array("\r\n","\r"), "\n", $line);
            //only accept an internal subset with no declaration separators
            preg_match("/<!DOCTYPE $whitespace+ ($name) $whitespace* \[ (.*) (?<!\])\]>/iuxms", $line, $matches);
            if(isset($matches[0]))
            {
                $dtd_full_match = $matches[0];
                $document_name = $matches[1];
                $dtd_raw = $matches[2];
                $dtd_raw_len = strlen($dtd_raw);
                break;
            }
        }
        
        if(!isset($dtd_raw))
        {
            throw new DTDMissingException();
        }
        else if($dtd_raw_len > $config['dtd_max_len'])
        {
            throw new DTDError("DTD exceeds maximum length.\nDTD RAW:\n$dtd_raw");
        }
        $dtd['dtd_raw'] = $dtd_raw;
        
        //now remove various unwanted parts of the dtd if they are present
        //remove PI's
        $dtd_raw = preg_replace("/ <\? $name $whitespace .* $whitespace? \?> /Uixsm", '', $dtd_raw);
        //remove comments
        $dtd_raw = preg_replace("/ <!-- .* --> /Uixsm", '', $dtd_raw);
        //remove parameter entity declarations
        $dtd_raw = preg_replace("/ $parameter_entity_declaration /ixsm", '', $dtd_raw);
        //remove external entitiy declarations
        $dtd_raw = preg_replace("/ $external_entity_declaration /ixsm", '', $dtd_raw);
        //remove any elements that have parameter entites for content
        $dtd_raw = preg_replace("/ $unpermitted_element_declarations /ixsm", '', $dtd_raw);
        //remove any ignore/include sections
        $dtd_raw = preg_replace("/ $conditional_declarations /ixsm", '', $dtd_raw);
        //remove any notation declarations
        $dtd_raw = preg_replace("/ $notation_declaration /ixsm", '', $dtd_raw);
        //remove unwanted attribute declarations
        $dtd_raw = preg_replace("/ $unpermitted_attribute_declarations /Uixsm", '', $dtd_raw);
        //now its safe to remove paramater entities (thereby removing any declaration separators)
        $dtd_raw = preg_replace("/ $parameter_entity_reference /ixsm", '', $dtd_raw);
        
        //clean up any resultant whitespace
        $dtd_raw = trim(preg_replace("/ \x{0A}(?:$whitespace+) /ixs", "\n", $dtd_raw));
        $dtd_raw = preg_replace("/ ($whitespace)(\\g{1})+ /ixs", "\\1", $dtd_raw);
        
        //now we should extract permitted elements, attributes, and entites.
        preg_match_all("/ $permitted_element_declarations /Uxuims", $dtd_raw, $matches, \PREG_OFFSET_CAPTURE);
        $elements = array();
        $i = 0;
        $chars_removed = 0;
        while(!empty($element = array_column($matches, $i)))
        {
            $dtd_raw = substr_replace($dtd_raw, "", $element[0][1] - $chars_removed, strlen($element[0][0]));
            $chars_removed += strlen($element[0][0]);
            $elements[] = array(
                'name'  =>  trim($element[1][0]),
                'contentspec'  =>  trim($element[2][0]),
            );
            $i++;
        }
        
        preg_match_all("/ $permitted_attribute_declarations /Uxuims", $dtd_raw, $matches, \PREG_OFFSET_CAPTURE);
        $attributes = array();
        $i = 0;
        $chars_removed = 0;
        while(!empty($attribute = array_column($matches, $i)))
        {
            $dtd_raw = substr_replace($dtd_raw, "", $attribute[0][1] - $chars_removed, strlen($attribute[0][0]));
            $chars_removed += strlen($attribute[0][0]);
            $attr_name = trim($attribute[1][0]);
            $attributes[$attr_name][] = array(
                'name'  =>  trim($attribute[2][0]),
                'type'  =>  trim($attribute[3][0]),
                'default'  =>  trim($attribute[4][0], " \t\n\r\x00\x0B\"'"),
            );
            $i++;
        }
        
        $entities = array();
        preg_match_all("/ $permitted_entity_declarations /Uxuims", $dtd_raw, $matches, \PREG_OFFSET_CAPTURE);
        $i = 0;
        $chars_removed = 0;
        while(!empty($entity = array_column($matches, $i)))
        {
            $dtd_raw = substr_replace($dtd_raw, "", $entity[0][1] - $chars_removed, strlen($entity[0][0]));
            $chars_removed += strlen($entity[0][0]);
            $entities[] = array(
                'name'   =>  trim($entity[1][0]),
                'value'   =>  trim($entity[2][0], " \t\n\r\x00\x0B\"'" ),
            );
            $i++;
        }
        
        //get rid of all whitespace
        $dtd_raw = preg_replace("/ $whitespace /ixs", "", $dtd_raw);
        if(strlen($dtd_raw) > 0)
        {
            //if any substring remains, then it is something we didn't yet account for, so just throw a fatal error so that we can deal with these on a case by case basis
            throw new DTDError("DTD blacklist failed.\nDTD RAW:\n$dtd_raw");
        }
                
        
        //the next step is to serialize the above extracted information by first parsing the element definitions
        foreach($elements as &$element)
        {
            $contentspec = $element['contentspec'];
            $i = 0;
            $char_buffer = "";
            $depth = -1;
            $positions = array(); // array holding $depth => $position associations to keep track of where we are
            $contentspec_buffer = array();  //holds parsed structures waiting to be pushed onto $contentspec_parsed. this is not a 2d array!
            while(false !== $char = substr($contentspec, $i++, 1))
            {
                if($char === "(")
                {
                    $depth++;
                    $positions[$depth] = 0;
                    $contentspec_buffer[$depth] = array();
                }
                else if(preg_match("/ $name_chars | [\?\*\+] | \# /xsium", $char))
                {
                    $char_buffer .= $char;
                }
                else if($char === "," || $char === "|")
                {
                    if($char_buffer !== "")
                    {
                        if(isset($contentspec_buffer[$depth]['type']))
                        {
                            $type = $contentspec_buffer[$depth]['type'];
                            $next_type = ($char === "," ?  "sequence" : "choice");
                            if($type !== $next_type)
                            {
                                throw new DTDError("Unrecognized DTD element: $element");
                            }
                        }
                        else
                        {
                            $contentspec_buffer[$depth]['type'] = ($char === "," ?  "sequence" : "choice");
                        }
                        //before dumping the buffer, make sure if the first char in the buffer is a # then it is a PCDATA element, otherwise the element name is ill-formed
                        $char_buffer = trim($char_buffer);
                        if($char_buffer[0] === "#" && strtoupper($char_buffer) !== "#PCDATA")
                        {
                            throw new DTDError("Unrecognized DTD element: $element");
                        }
                        //if element token is followed by a modifidier then split the modifier and element token apart
                        else if(strcspn($char_buffer, "*+?") < strlen($char_buffer))
                        {
                            $char_buffer = array(substr($char_buffer, 0, -1), substr($char_buffer, -1, 1));
                        }
                        $contentspec_buffer[$depth][$positions[$depth]] = $char_buffer;
                    }
                    $positions[$depth]++;
                    $char_buffer = "";
                }
                else if($char === ")")
                {
                    if($char_buffer !== "")
                    {
                        //before dumping the buffer, make sure if the first char in the buffer is a # then it is a PCDATA element, otherwise the element name is ill-formed
                        $char_buffer = trim($char_buffer);
                        if($char_buffer[0] === "#" && strtoupper($char_buffer) !== "#PCDATA")
                        {
                            throw new DTDError("Unrecognized DTD element: $element");
                        }
                        //if element token is followed by a modifidier then split the modifier and element token apart
                        else if(strcspn($char_buffer, "*+?") < strlen($char_buffer))
                        {
                            $char_buffer = array(substr($char_buffer, 0, -1), substr($char_buffer, -1, 1));
                        }
                        $contentspec_buffer[$depth][$positions[$depth]] = $char_buffer;
                    }
                    $content = $contentspec_buffer[$depth];
                    unset($contentspec_buffer[$depth]);
                    $depth--;
                    if($depth < 0)
                    {
                        //if we're back at the top level, don't append. just flatten
                        $depth = 0;
                        $contentspec_buffer = $content;
                    }
                    else
                    {
                        $contentspec_buffer[$depth][$positions[$depth]] = $content; //we're finished parsing a single nested structure so now we pop it onto the enveloping structure which was set as the most recent depth
                    }
                    $char_buffer = "";
                }
            }
            //NOTE the XML1.0 specification stipulates that the order of elements enumerated in a sequence is signficant. hence, we cannnot and need not order the elements in a sequence ourselves for the
            //purpose of canonicalization
            $element['parsed_contentspec'] = $contentspec_buffer;
            unset($element['contentspec']);
        }
        
        usort($elements, array($this,"usort_by_column_name"));
        
        asort($attributes);
        foreach($attributes as &$attribute)
        {
            usort($attribute, array($this,"usort_by_column_name"));
        }
        
        usort($entities, array($this,"usort_by_column_name"));
        
        $this->document_name = $document_name;
        $this->elements = $elements;
        $this->attributes = $attributes;
        $this->entities = $entities;
        return  serialize(array('document_name' => $this->document_name, 'elements' => $this->elements, 'attributes' => $this->attributes, 'entities' => $this->entities));
    }
    
    private function usort_by_column_name($array1, $array2)
    {
        return strcmp(trim($array1['name']), trim($array2['name']));
    }
}
