<?php

namespace MKDict\Unicode;

use MKDict\FileResource\GZFileResource;

class Unicode
{
    const UNICODE_DECOMPOSITION_CANONICAL = 0;
    const UNICODE_DECOMPOSITION_COMPATIBILITY = 1;
    const UTF_NORMALIZE_NFD = 1;
    const UTF_NORMALIZE_NFKD = 2;
    const UTF_NORMALIZE_NFC = 3;
    const UTF_NORMALIZE_NFKC = 4;
    const UTF_NORMALIZE_NFD_CASEFOLD = 5;
    const UTF_NORMALIZE_NFKD_CASEFOLD = 6;
    
    const UNICODE_HANGUL_SYLLABLE_FIRST = 0xAC00;
    const UNICODE_HANGUL_SYLLABLE_LAST = 0xD7AF;
    const UNICODE_HANGUL_SBASE = 0xAC00;
    const UNICODE_HANGUL_LBASE = 0x1100;
    const UNICODE_HANGUL_VBASE = 0x1161;
    const UNICODE_HANGUL_TBASE = 0x11A7;
    const UNICODE_HANGUL_SCOUNT = 11172;
    const UNICODE_HANGUL_LCOUNT = 19;
    const UNICODE_HANGUL_VCOUNT = 21;
    const UNICODE_HANGUL_TCOUNT = 28;
    const UNICODE_HANGUL_NCOUNT = 588;
    
    //BOM signatures (U+FEFF) in the UTF encoding schemes see Unicode 23.8 http://www.unicode.org/versions/Unicode7.0.0/ch23.pdf
    const UTF8_BOM = "\xEF\xBB\xBF";
    const UTF16_BOM = "\xFE\xFF";
    const UTF16_BOM_REVERSED = "\xFF\xFE";
    const UTF32_BOM = "\x00\x00\xFE\xFF";
    const UTF32_BOM_REVERSED = "\xFF\xFE\x00\x00";
    
    //replacement character U+FFFD
    const UTF8_REPLACEMENT = "\xEF\xBF\xBD";
    
    /**
     * Verifies if text content is valid UTF-8.
     *
     * Verifies if the text content conforms to the UTF-8 scheme defined in http://www.unicode.org/versions/Unicode7.0.0/ch03.pdf 
     * definition #D92 by analyzing the bit distributions. See tables 3-6 and 3-7.
     * Ignores the unicode BOM character U+FEFF if present (which would be an error).
     *
     * @param GZFileResource $file A file handle opened by gzopen()
     *
     * @return boolean True if text content is valid UTF-8 false otherwise
     */
    public static function utf8_validate_file(GZFileResource $file)
    {
        global $config;
        
        $first_read = true;
        
        $file->rewind();
        
        do
        {
            $utf_char_stream = $file->read($config['GZ_buffer_rw_size']);
            
            //echo "utf_char_stream:\n".Unicode::hexdump($utf_char_stream)."\n";
            //die();
            
            if($first_read)
            {
                if(self::UTF8_BOM === substr($utf_char_stream, 0, 3))
                {
                    $utf_char_stream = substr($utf_char_stream, 3);
                }
                $first_read = false;
            }
            
            $stream_len = strlen($utf_char_stream);
            $pos = 0;
            
            //UTF-8 is a variable length encoding scheme but we are not starting at an arbitrary point in the byte stream
            while($pos < $stream_len)
            {
                $first_byte = ord($utf_char_stream[$pos]);
                
                //character is one byte long
                if(($first_byte & 0x80) === 0x00)
                {
                    //fist byte is valid, continue the loop
                    $pos++;
                    continue;
                }
                //character is two bytes long
                else if(($first_byte & 0xE0) === 0xC0)
                {
                    if($first_byte < 0xC2)
                    {
                        return false;
                    }
                    
                    if(isset($utf_char_stream[$pos+1]))
                    {
                        $second_byte = ord($utf_char_stream[$pos+1]);
                    }
                    else
                    {
                        $second_byte = ord($file->read(1));
                    }
                    
                    //check if byte is between 80...BF
                    if(($second_byte & 0xC0) === 0xC0)
                    {
                        return false;
                    }
                    
                    $pos += 2;
                    continue;
                }
                //character is three bytes long
                else if(($first_byte & 0xF0) === 0xE0)
                {
                    $second_byte_lower_bound = 0x80;
                    $second_byte_upper_bound = 0xBF;
                    
                    if($first_byte === 0xE0)
                    {
                        $second_byte_lower_bound = 0xA0;
                    }
                    else if($first_byte === 0xED)
                    {
                        $second_byte_upper_bound = 0x9F;
                    }
                    
                    if(isset($utf_char_stream[$pos+1]))
                    {
                        $second_byte = ord($utf_char_stream[$pos+1]);
                    }
                    else
                    {
                        $second_byte = ord($file->read(1));
                    }
                    
                    if($second_byte < $second_byte_lower_bound || $second_byte > $second_byte_upper_bound)
                    {
                        return false;
                    }
                    
                    if(isset($utf_char_stream[$pos+2]))
                    {
                        $third_byte = ord($utf_char_stream[$pos+2]);
                    }
                    else
                    {
                        $third_byte = ord($file->read(1));
                    }
                    
                    //check if byte is between 80...BF
                    if(($third_byte & 0xC0) === 0xC0)
                    {
                        return false;
                    }
                    
                    $pos += 3;
                    continue;
                }
                //character is four bytes long
                else if(($first_byte & 0xF0) === 0xF0)
                {
                    if($first_byte >= 0xF5)
                    {
                        return false;
                    }
                    
                    if(isset($utf_char_stream[$pos+1]))
                    {
                        $second_byte = ord($utf_char_stream[$pos+1]);
                    }
                    else
                    {
                        $second_byte = ord($file->read(1));
                    }
                    
                    $second_byte_lower_bound = 0x80;
                    $second_byte_upper_bound = 0xBF;
                    
                    if($first_byte === 0xF0)
                    {
                        $second_byte_lower_bound = 0x90;
                    }
                    else if($first_byte === 0xF4)
                    {
                        $second_byte_upper_bound = 0x8F;
                    }
                    
                    if($second_byte < $second_byte_lower_bound || $second_byte > $second_byte_upper_bound)
                    {
                        return false;
                    }
                    
                    if(isset($utf_char_stream[$pos+2]))
                    {
                        $third_byte = ord($utf_char_stream[$pos+2]);
                    }
                    else
                    {
                        $third_byte = ord($file->read(1));
                    }
                    
                    //check if byte is between 80...BF
                    if(($third_byte & 0xC0) === 0xC0)
                    {
                        return false;
                    }
                    
                    if(isset($utf_char_stream[$pos+2]))
                    {
                        $fourth_byte = ord($utf_char_stream[$pos+2]);
                    }
                    else
                    {
                        $fourth_byte = ord($file->read(1));
                    }
                    
                    //check if byte is between 80...BF
                    if(($fourth_byte & 0xC0) === 0xC0)
                    {
                        return false;
                    }
                    
                    $pos += 4;
                    continue;
                }
                else
                {
                    return false;
                }
            }
        }
        while(!$file->feof());
        
        return true;
    }
    
    /**
     * Returns the hex representation of a string
     *
     * PHP strings are stored as byte sequences.
     *
     * @param string $text The string to dump
     * @param boolean $output_raw True to precede each nibble with a '\x' literal ie "EF6D" or "\xEF\x6D"
     *
     * @return string The hexdump as a string
     */
    public static function hexdump($text, $output_raw=false)
    {
        $val = "";
        
        if($output_raw)
        {
            $format_string = "%X";
        }
        else
        {
            $format_string = "\\x%X";
        }
        
        for($i=0,$len=strlen($text); $i<$len; $i++)
        {
            $val .= sprintf($format_string, ord($text[$i]));
        }
        
        return $val;
    }
    
    /**
     * Get the Unicode Scalar Point from a UTF-8 char
     *
     * Converts a valid UTF-8 byte sequence into its corresponding Unicode Scalar Point representation
     * 
     * @param string $chr The valid UTF-8 character to convert. If more then one character is given in the string, returns input.
     *
     * @return int The Unicode Scalar Point as an integer
     */
    public static function utf8_utf($chr)
    {
        switch (strlen($chr))
        {
            case 1:
                return ord($chr);

            case 2:
                return ((ord($chr[0]) & 0x1F) << 6) | (ord($chr[1]) & 0x3F);

            case 3:
                return ((ord($chr[0]) & 0x0F) << 12) | ((ord($chr[1]) & 0x3F) << 6) | (ord($chr[2]) & 0x3F);

            case 4:
                return ((ord($chr[0]) & 0x07) << 18) | ((ord($chr[1]) & 0x3F) << 12) | ((ord($chr[2]) & 0x3F) << 6) | (ord($chr[3]) & 0x3F);

            default:
                return $chr;
        }
    }
    
    /**
     * Get the UTF-8 encoding from a given Unicode Scalar Point
     *
     * Returns a valid UTF-8 byte sequence representing the provided Unicode Scalar Point as an integer, a character literal, or the hexdump as a string
     * 
     * @param string|int $cp The unicode scalar code point
     * @param string $format The representation of the code point you want. Values are "int", "char", or "byte_string"
     *
     * @return mixed The UTF-8 value as an int, a regular string, or as a hexdump
     */
    public static function utf_to_utf8($cp, $format="byte_string")
    {
        if(is_string($cp))
        {
            $cp = hexdec($cp);
        }
        
        $bytes = array();
        
        if($cp < 0x7F)
        {
            $bytes[0] = $cp;
        }
        else if($cp < 0x7FF)
        {
            $bytes[0] = 0x80 | ($cp & 0x3F);
            $bytes[1] = 0xC0 | ($cp>>6 & 0x1F);
        }
        else if($cp < 0xFFFF)
        {
            $bytes[0] = 0x80 | ($cp & 0x3F);
            $bytes[1] = 0x80 | ($cp>>6 & 0x3F);
            $bytes[2] = 0xE0 | ($cp>>12 & 0x0F);
        }
        else if($cp < 0x1FFFFF)
        {
            $bytes[0] = 0x80 | ($cp & 0x3F);
            $bytes[1] = 0x80 | ($cp>>6 & 0x3F);
            $bytes[2] = 0x80 | ($cp>>12 & 0x3F);
            $bytes[3] = 0xF0 | ($cp>>18 & 0x07);
        }
        
        if(strtolower($format) === "int")
        {
            $byte_value = 0;
            $byte_shifter = 0;
            foreach($bytes as $byte)
            {
                $byte_value |= $byte << $byte_shifter;
                $byte_shifter += 8;
            }
            return $byte_value;
        }
        else if(strtolower($format) === "char")
        {
            $char_value = "";
            $byte_shifter = 0;
            foreach(array_reverse($bytes) as $byte)
            {
                $char_value .= chr($byte);
                $byte_shifter += 8;
            }
            return $char_value;
        }
        else if(strtolower($format) === "byte_string")
        {
            $byte_value = "";
            foreach(array_reverse($bytes) as $byte)
            {
                //$byte_value .= sprintf("\\x%02X", $byte);
                $byte_value .= sprintf("%02X", $byte);
            }
            return $byte_value;
        }
    }
    
    /**
     * The mb equivalent of PHP's substr_replace()
     *
     * Returns the input string with the replacement made
     * 
     * @param string $text The string into which the replacement is to be made
     * @param string $replacement The replacement value
     * @param int $start The index at which to begin replacement
     * @param int $length The number of chars to be replaced
     *
     * @return string The input string with the replacement made
     */
    public static function mb_substr_replace($text, $replacement, $start, $length=0)
    {
        //TODO remove the +1
        return mb_substr($text, 0, $start) . $replacement . mb_substr($text, $start + $length + 1);
    }
    
    /**
     * Unicode's Hangul Decomposition algorithm
     *
     * Decomposes Hangul syllables according to http://www.unicode.org/versions/Unicode7.0.0/ch03.pdf section 3.12 Conjoining Jamo Behaviour
     * 
     * @param string|int $hangul_syllable The Hangul syllable to decompose
     *
     * @return string A string containing the decomposed Hangul syllable sequence
     */
    public static function decompose_hangul($hangul_syllable)
    {
        if(is_string($hangul_syllable))
        {
            $hangul_syllable = hexdec($hangul_syllable);
        }
        $sindex = $hangul_syllable - self::UNICODE_HANGUL_SBASE;
        $lindex = floor($sindex / self::UNICODE_HANGUL_NCOUNT);
        $vindex = floor(($sindex % self::UNICODE_HANGUL_NCOUNT) / self::UNICODE_HANGUL_TCOUNT);
        $tindex = $sindex % self::UNICODE_HANGUL_TCOUNT;
        
        $lpart = self::UNICODE_HANGUL_LBASE + $lindex;
        $vpart = self::UNICODE_HANGUL_VBASE + $vindex;
        $tpart = self::UNICODE_HANGUL_TBASE + $tindex;
        
        $hs_decomp = self::utf_to_utf8($lpart, "char").self::utf_to_utf8($vpart, "char");
        if($tindex > 0)
        {
            $hs_decomp .= self::utf_to_utf8($tpart, "char");
        }
        
        return $hs_decomp;
    }
    
    /**
     * Unicode's Decomposition algorithm
     *
     * See http://www.unicode.org/versions/Unicode7.0.0/ch03.pdf section 3.11 Normalization form
     * 
     * @param string $text The valid UTF-8 string to decompose
     * @param string $type The decomposition type. Either UNICODE_DECOMPOSITION_CANONICAL or UNICODE_DECOMPOSITION_COMPATIBILITY
     *
     * @return string The decomposed string
     */
    public static function utf8_decompose($text, $type = self::UNICODE_DECOMPOSITION_CANONICAL)
    {
        global $config;
        
        require_once "$config[data_dir]/$config[canonical_decompositions]";
        require_once "$config[data_dir]/$config[compatibility_decompositions]";
        require_once "$config[data_dir]/$config[ccc_class_map]";
        
        $decomp_map1 = &$GLOBALS['canon_decomp'];
        if($type === self::UNICODE_DECOMPOSITION_COMPATIBILITY)
        {
            $decomp_map2 = &$GLOBALS['compat_decomp'];
            $decomp_map = array_merge($decomp_map1, $decomp_map2);
        }
        else
        {
            $decomp_map = &$decomp_map1;
        }
        
        $ccc_map = &$GLOBALS['ccc_class'];
        
        //first, recursively decompose
        $pos = 0;
        while(($char = mb_substr($text, $pos, 1)) !== "")
        {
            $cp = self::utf8_utf($char);
            if($cp > self::UNICODE_HANGUL_SYLLABLE_FIRST && $cp < self::UNICODE_HANGUL_SYLLABLE_LAST)
            {
                $hangul_decomp = self::decompose_hangul($cp);
                $text = self::mb_substr_replace($text, $hangul_decomp, $pos);
                $pos += mb_strlen($hangul_decomp);
            }
            else
            {
                if(isset($decomp_map[$char]))
                {
                    $text = self::mb_substr_replace($text, $decomp_map[$char], $pos);
                }
                else
                {
                    $pos++;
                }
            }
        }
        
        //second, put into canonical order
        $char_buffer = array();
        for($i=0, $len = mb_strlen($text); $i < $len; $i++)
        {
            $char = mb_substr($text, $i, 1);
            if(!isset($ccc_map[$char]))
            {
                if(!empty($char_buffer))
                {
                    $insert_position = array_keys($char_buffer)[0];
                    usort($char_buffer, function($char1, $char2) use ($ccc_map){
                        return $ccc_map[$char1] === $ccc_map[$char2] ?  USORT_EQUALITY_CONSTANT : ( $ccc_map[$char1] < $ccc_map[$char2] ? -1 : 1);
                    });
                    $text = self::mb_substr_replace($text, implode($char_buffer), $insert_position, count($char_buffer) - 1);
                    $char_buffer = array();
                }
            }
            else
            {
                if($i == 0)
                {
                    //$mk_exceptions->invalid_utf8("Defective combining character sequence found");
                }
                else
                {
                    $char_buffer[$i] = $char;
                    //need to dump buffer if on last iteration
                    if($i == $len - 1)
                    {
                        if(!empty($char_buffer))
                        {
                            $insert_position = array_keys($char_buffer)[0];
                            usort($char_buffer, function($char1, $char2) use ($ccc_map){
                                return $ccc_map[$char1] === $ccc_map[$char2] ?  USORT_EQUALITY_CONSTANT : ( $ccc_map[$char1] < $ccc_map[$char2] ? -1 : 1);
                            });
                            $text = self::mb_substr_replace($text, implode($char_buffer), $insert_position, count($char_buffer) - 1);
                            $char_buffer = array();
                        }
                    }
                }
            }
        }
        return $text;
    }
    
    /**
     * Unicode's Recomposition algorithm
     *
     * See http://www.unicode.org/versions/Unicode7.0.0/ch03.pdf section 3.11 Normalization form
     * 
     * @param string $text The valid UTF-8 string to recompose
     * @param string $type The decomposition type that was used prior. Either UNICODE_DECOMPOSITION_CANONICAL or UNICODE_DECOMPOSITION_COMPATIBILITY
     *
     * @return string The recomposed string
     * 
     * @TODO test hangul recomposition
     */
    public static function utf8_recompose($text, $type = self::UNICODE_DECOMPOSITION_CANONICAL)
    {
        global $config;
        
        require_once "$config[data_dir]/$config[nfc_qc]";
        require_once "$config[data_dir]/$config[nfkc_qc]";
        require_once "$config[data_dir]/$config[ccc_class_map]";
        require_once "$config[data_dir]/$config[primary_composites]";
        
        if($type === self::UNICODE_DECOMPOSITION_CANONICAL)
        {
            $qc_map = &$GLOBALS['nfc_qc'];
        }
        else if($type === self::UNICODE_DECOMPOSITION_COMPATIBILITY)
        {
            $qc_map = &$GLOBALS['nfkc_qc'];
        }
        $ccc_map = &$GLOBALS['ccc_class'];
        $primary_composites = &$GLOBALS['primary_composites'];
        
        $pos = 1;
        while(($char = mb_substr($text, $pos, 1)) !== "")
        {
            $replaced  = false;
            if(in_array($char, $qc_map))
            {
                $pos_ = $pos - 1;
                while($pos_ >= 0)
                {
                    $char_ = mb_substr($text, $pos_, 1);
                    if(!isset($ccc_class[$char_]))
                    {
                        //if $char_ is blocked from $char, we have to continue on
                        if($pos - $pos_ == 1)
                        {
                            $preceeding_char = mb_substr($text, $pos-1, 1);
                            if(isset($ccc_class[$preceeding_char]) && isset($ccc_class[$char]) && $ccc_class[$preceeding_char] >= $ccc_class[$char])
                            {
                                $pos++;
                                break;
                            }
                        }
                        $candidate_char = $char_ . $char;
                        if(isset($primary_composites[$candidate_char]))
                        {
                            $text = self::mb_substr_replace($text, $primary_composites[$candidate_char], $pos_);
                            $text = self::mb_substr_replace($text, "", $pos);
                            $replaced = true;
                            break;
                        }
                    }
                    $pos_--;
                }
                if(!$replaced)
                {
                    $pos++;
                }
                continue;
            }
            else
            {
                $pos++;
                continue;
            }
        }
        
        return $text;
    }
    
    /**
     * Default Caseless Matching. A lowercase operation generalized to UTF8 characters.
     *
     * The casefolding is accomplished with a pre-generated case map. See http://www.unicode.org/versions/Unicode7.0.0/ch03.pdf section 3.13 Default Case Algorithms.
     * Note this casefolding does not preserve normalized text.
     * 
     * @param string $text The valid UTF-8 string to casefold
     *
     * @return string The case-folded string
     */
    public static function casefold($text)
    {
        global $config;
        
        require_once "$config[data_dir]/$config[case_mapping_f]";
        
        return strtr($text, $GLOBALS['case_map']);
    }
    
    /**
     * Canonical Caseless Matching. A lowercase operation generalized to UTF8 characters that also preserves normalized text.
     *
     * The casefolding is accomplished with a pre-generated case map. See http://www.unicode.org/versions/Unicode7.0.0/ch03.pdf section 3.13 Default Case Algorithms.
     * Similar to casefold($text) but will additionaly equate sequences which differ only in their normal forms. Note this function does return decomposed text, do not
     * use for UI strings.
     * 
     * @see casefold($text)
     * 
     * @param string $text The valid UTF-8 string to casefold
     *
     * @return string The case-folded string
     */
    public static function nfd_casefold($text)
    {
        return self::nfd(self::casefold(self::nfd($text)));
    }
    
    /**
     * Compatibility Caseless Matching. A lowercase operation generalized to UTF8 characters that also preserves normalized text and collapses compatibility differences.
     *
     * The casefolding is accomplished with a pre-generated case map. See http://www.unicode.org/versions/Unicode7.0.0/ch03.pdf section 3.13 Default Case Algorithms.
     * Note this casefolding doesn't not preserve normalized text. Similar to casefold($text) but preserves normalization.
     * 
     * @see casefold($text)
     * @see nfd_casefold($text)
     * 
     * @param string $text The valid UTF-8 string to casefold
     *
     * @return string The case-folded string
     */
    public static function nfkd_casefold($text)
    {
        return self::nfkd(self::casefold(self::nfkd(self::casefold(self::nfd($text)))));
    }
    
    /**
     * Unicode Normalization Form D: Canonical Decomposition
     *
     * See Unicode Standard Annex #15 Normalization Forms http://www.unicode.org/reports/tr15/tr15-41.html
     * 
     * @see nfkd($text)
     * @see nfc($text)
     * @see nfkc($text)
     * @see normalize_text($text, $mode, $entity)
     * 
     * @param string $text The valid UTF-8 string to decompose
     *
     * @return string The decomposed string
     */
    private static function nfd($text)
    {
        return self::utf8_decompose($text);
    }
    
    /**
     * Unicode Normalization Form KD: Compatibility Decomposition
     *
     * See Unicode Standard Annex #15 Normalization Forms http://www.unicode.org/reports/tr15/tr15-41.html
     * 
     * @see nfd($text)
     * @see nfc($text)
     * @see nfkc($text)
     * @see normalize_text($text, $mode, $entity)
     * 
     * @param string $text The valid UTF-8 string to decompose
     *
     * @return string The decomposed string
     */
    private static function nfkd($text)
    {
        return self::utf8_decompose($text, self::UNICODE_DECOMPOSITION_COMPATIBILITY);
    }
    
    /**
     * Unicode Normalization Form C: Canonical Decomposition followed by Canonical Composition
     *
     * See Unicode Standard Annex #15 Normalization Forms http://www.unicode.org/reports/tr15/tr15-41.html
     * 
     * @see nfd($text)
     * @see nfkd($text)
     * @see nfkc($text)
     * @see normalize_text($text, $mode, $entity)
     * 
     * @param string $text The valid UTF-8 string to decompose
     *
     * @return string The composed string
     */
    private static function nfc($text)
    {
        return self::utf8_recompose(self::utf8_decompose($text));
    }
    
    /**
     * Unicode Normalization Form KC: Compatibility Decomposition followed by Canonical Composition
     *
     * See Unicode Standard Annex #15 Normalization Forms http://www.unicode.org/reports/tr15/tr15-41.html
     * 
     * @see nfd($text)
     * @see nfkd($text)
     * @see nfc($text)
     * @see normalize_text($text, $mode, $entity)
     * 
     * @param string $text The valid UTF-8 string to decompose
     *
     * @return string The composed string
     */
    private static function nfkc($text)
    {
        return self::utf8_recompose(self::utf8_decompose($text, self::UNICODE_DECOMPOSITION_COMPATIBILITY), self::UNICODE_DECOMPOSITION_COMPATIBILITY);
    }
    
    /**
     * Markup-aware text normalization. Normalizes text but avoids text between markup.
     *
     * Specify a markup token and this function will normalize all text except any text that is encapsulated by successive markup tokens. If no markup token is
     * specified, then all text is normalized.
     * 
     * @param string $text The valid UTF-8 string to decompose
     * @param string $mode Which Unicode normal form to use
     * @param string $entity The markup delimiter
     *
     * @return string The composed string
     */
    public static function normalize_text($text, $mode, $entity = "")
    {
        switch($mode)
        {
            case self::UTF_NORMALIZE_NFD:
                $func = "nfd";
                break;
            
            case self::UTF_NORMALIZE_NFKD:
                $func = "nfkd";
                break;
            
            case self::UTF_NORMALIZE_NFC:
                $func = "nfc";
                break;
            
            case self::UTF_NORMALIZE_NFKC:
                $func = "nfkc";
                break;
            
            case self::UTF_NORMALIZE_NFD_CASEFOLD:
                $func = "nfd_casefold";
                break;
            
            case self::UTF_NORMALIZE_NFKD_CASEFOLD:
                $func = "nfkd_casefold";
                break;
            
            default:
                return false;
        }

        if(!empty($entity))
        {
            $tokens = preg_split("/ " . $entity . " /ixsm", $text, -1, PREG_SPLIT_NO_EMPTY);
            $entity_token = false;
            //looping through all the consecutive tokens created by preg_split we need to keep track of when we enter and exit
            //a markup delimited substring
            foreach($tokens as &$token)
            {
                //entering a markup zone
                if(!$entity_token && $token === $entity)
                {
                    $entity_token = true;
                }
                //exiting a markup zone
                else if($entity_token && $token === $entity)
                {
                    $entity_token = false;
                }
                //inside a markup zone
                else if($entity_token) {}
                //outside a markup zone
                else
                {
                    $token = self::$func($token);
                }
            }
            unset($token);
            $result = trim(implode($tokens));
            return $result;
        }
        
        return trim($func($text));
    }
}
