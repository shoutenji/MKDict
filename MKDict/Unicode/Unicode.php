<?php

namespace MKDict\Unicode;

use MKDict\FileResource\FileResouce;

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
    const UTF8_REPLACEMENT = "EF\xBF\xBD";
    
    /**
     * Verifies if text content is valid UTF-8.
     *
     * Verifies if the text content conforms to the UTF-8 scheme defined in http://www.unicode.org/versions/Unicode7.0.0/ch03.pdf 
     * definition #D92 by analyzing the bit distributions. See tables 3-6 and 3-7.
     * Ignores the unicode BOM character U+FEFF if present (which would be an error).
     *
     * @param resource $gzfile_handle A file handle opened by gzopen()
     *
     * @return boolean True if text content is valid UTF-8 false otherwise
     */
    public static function utf8_validate_file(FileResouce $file)
    {
        global $config;
        
        $first_read = true;
        
        do
        {
            $utf_char_stream = $file->read($config['GZ_buffer_rw_size']);
            
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
                    
                    $pos++;
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
        while(!$file->feof(1));
        
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
    /*
    function utf8_decompose($text, $type = UNICODE_DECOMPOSITION_CANONICAL)
    {
        global $mk_exceptions;
        
        require_once GENERATED_FILE_CANONICAL_DECOMP;
        require_once GENERATED_FILE_COMPATABILITY_DECOMP;
        require_once GENERATED_FILE_CCC;
        
        $decomp_map1 = &$GLOBALS['canon_decomp'];
        if($type === UNICODE_DECOMPOSITION_COMPATIBILITY)
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
            $cp = utf8_utf($char);
            if($cp > UNICODE_HANGUL_SYLLABLE_FIRST && $cp < UNICODE_HANGUL_SYLLABLE_LAST)
            {
                $hangul_decomp = decompose_hangul($cp);
                $text = mb_substr_replace($text, $hangul_decomp, $pos);
                $pos += mb_strlen($hangul_decomp);
            }
            else
            {
                if(isset($decomp_map[$char]))
                {
                    $text = mb_substr_replace($text, $decomp_map[$char], $pos);
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
                    $text = mb_substr_replace($text, implode($char_buffer), $insert_position, count($char_buffer) - 1);
                    $char_buffer = array();
                }
            }
            else
            {
                if($i == 0)
                {
                    $mk_exceptions->invalid_utf8("Defective combining character sequence found");
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
                            $text = mb_substr_replace($text, implode($char_buffer), $insert_position, count($char_buffer) - 1);
                            $char_buffer = array();
                        }
                    }
                }
            }
        }

        return $text;
    }
    */
}
