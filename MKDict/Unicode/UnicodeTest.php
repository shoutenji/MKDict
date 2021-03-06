<?php

namespace MKDict\Unicode;

use MKDict\Unicode\Unicode;
use MKDict\Unicode\Exception\UnicodeTestCaseFailure;

/**
 * Limited test cases for class Unicode. These cases come from the official Unicode http://www.unicode.org/reports/tr15/tr15-41.html#Examples
 * 
 * @see MKDict\Unicode\Unicode
 *
 * @author Taylor B <taylorbrontario@riseup.net>
 * 
 * @todo this class needs more testing: edge cases, randomization mixing of valid and invalid unicode, etc.
 * @todo hangul recomposition not tested
 */
class UnicodeTest
{
    protected $D = "\x44";
    protected $D_dot_above = "\xE1\xB8\x8A";
    protected $D_dot_below = "\xE1\xB8\x8C";
    protected $dot_above = "\xCC\x87";
    protected $dot_below = "\xCC\xA3";
    protected $horn = "\xCC\x9B";
    protected $E = "\x45";
    protected $E_macron_grave = "\xE1\xB8\x94";
    protected $E_macron = "\xC4\x92";
    protected $E_grave = "\xC3\x88";
    protected $macron = "\xCC\x84";
    protected $grave = "\xCC\x80";
    protected $angstrom = "\xE2\x84\xAB";
    protected $A = "\x41";
    protected $A_ring = "\xC3\x85";
    protected $ring = "\xCC\x8A";
    protected $A_diaeresis = "\xC3\x84";
    protected $diaeresis = "\xCC\x88";
    protected $ffi_ligature = "\xEF\xAC\x83";
    protected $IV = "\xE2\x85\xA3";
    protected $ga = "\xE3\x82\xAC";
    protected $ka = "\xE3\x82\xAB";
    protected $ten = "\xE3\x82\x99";
    protected $hw_ka = "\xEF\xBD\xB6";
    protected $hw_ten = "\xEF\xBE\x9E";
    
    /**
     * Run unicode tests
     *
     * Excute various unicode test metrics
     *
     * @return void
     */
    public function run_tests()
    {
        $this->test_nfd();
        $this->test_nfkd();
        $this->test_nfc();
        $this->test_nfkc();
        $this->test_case_fold();
    }
    
    /**
     * Test implementation of Unicode's NFD algorithm.
     *
     * NFD = Canonical Decomposition
     *
     * @throws UnicodeTestCaseFailure if decomposition result fails to match the correct value
     * 
     * @see http://www.unicode.org/reports/tr15/tr15-41.html Unicode Standard Annex 7.0 #15 Normalization Forms
     * 
     * @return void
     */
    public function test_nfd()
    {
        $nfd = array(
            "{$this->D_dot_above}" => "{$this->D}{$this->dot_above}",
            "{$this->D}{$this->dot_above}" => "{$this->D}{$this->dot_above}",
            "{$this->D_dot_below}{$this->dot_above}" => "{$this->D}{$this->dot_below}{$this->dot_above}",
            "{$this->D_dot_above}{$this->dot_below}" => "{$this->D}{$this->dot_below}{$this->dot_above}",
            "{$this->D}{$this->dot_above}{$this->dot_below}" => "{$this->D}{$this->dot_below}{$this->dot_above}",
            "{$this->D}{$this->dot_above}{$this->horn}{$this->dot_below}" => "{$this->D}{$this->horn}{$this->dot_below}{$this->dot_above}",
            "{$this->E_macron_grave}" => "{$this->E}{$this->macron}{$this->grave}",
            "{$this->E_macron}{$this->grave}" => "{$this->E}{$this->macron}{$this->grave}",
            "{$this->E_grave}{$this->macron}" => "{$this->E}{$this->grave}{$this->macron}",
            "{$this->angstrom}" => "{$this->A}{$this->ring}",
            "{$this->A_ring}" => "{$this->A}{$this->ring}",

            "{$this->A_diaeresis}ffin" => "{$this->A}{$this->diaeresis}ffin",
            "{$this->A_diaeresis}{$this->ffi_ligature}n" => "{$this->A}{$this->diaeresis}{$this->ffi_ligature}n",
            "Henry IV" => "Henry IV",
            "Henry {$this->IV}" => "Henry {$this->IV}",
            "{$this->ga}" => "{$this->ka}{$this->ten}",
            "{$this->ka}{$this->ten}" => "{$this->ka}{$this->ten}",
            "{$this->hw_ka}{$this->hw_ten}" => "{$this->hw_ka}{$this->hw_ten}",
            "{$this->ka}{$this->hw_ten}" => "{$this->ka}{$this->hw_ten}",
            "{$this->hw_ka}{$this->ten}" => "{$this->hw_ka}{$this->ten}",
        );
        
        $i = -1;
        foreach($nfd as $test_value => $test_result)
        {
            $i++;
            $decomp = Unicode::utf8_decompose($test_value);
            if($decomp !== $test_result)
            {
                throw new UnicodeTestCaseFailure(
                    array(
                        "index" => $i,
                        "input_literal" => $test_value,
                        "input_hex" => Unicode::hexdump($test_value),
                        "expected_output" => $test_result,
                        "expected_output_hex" => Unicode::hexdump($test_result),
                        "actual_output" => $decomp,
                        "actual_output_hex" => Unicode::hexdump($decomp),
                    )
                );
            }
        }
    }
    
    /**
     * Test implementation of Unicode's NFKD algorithm.
     *
     * NFKD = Compatibility Decomposition
     *
     * @throws UnicodeTestCaseFailure if decomposition result fails to match the correct value
     * 
     * @see http://www.unicode.org/reports/tr15/tr15-41.html Unicode Standard Annex 7.0 #15 Normalization Forms
     * 
     * @return void
     */
    public function test_nfkd()
    {
        $nfkd = array(
            "{$this->D_dot_above}" => "{$this->D}{$this->dot_above}",
            "{$this->D}{$this->dot_above}" => "{$this->D}{$this->dot_above}",
            "{$this->D_dot_below}{$this->dot_above}" => "{$this->D}{$this->dot_below}{$this->dot_above}",
            "{$this->D_dot_above}{$this->dot_below}" => "{$this->D}{$this->dot_below}{$this->dot_above}",
            "{$this->D}{$this->dot_above}{$this->dot_below}" => "{$this->D}{$this->dot_below}{$this->dot_above}",
            "{$this->D}{$this->dot_above}{$this->horn}{$this->dot_below}" => "{$this->D}{$this->horn}{$this->dot_below}{$this->dot_above}",
            "{$this->E_macron_grave}" => "{$this->E}{$this->macron}{$this->grave}",
            "{$this->E_macron}{$this->grave}" => "{$this->E}{$this->macron}{$this->grave}",
            "{$this->E_grave}{$this->macron}" => "{$this->E}{$this->grave}{$this->macron}",
            "{$this->angstrom}" => "{$this->A}{$this->ring}",
            "{$this->A_ring}" => "{$this->A}{$this->ring}",

            "{$this->A_diaeresis}ffin" => "{$this->A}{$this->diaeresis}ffin",
            "{$this->A_diaeresis}{$this->ffi_ligature}n" => "{$this->A}{$this->diaeresis}ffin",
            "Henry IV" => "Henry IV",
            "Henry {$this->IV}" => "Henry IV",
            "{$this->ga}" => "{$this->ka}{$this->ten}",
            "{$this->ka}{$this->ten}" => "{$this->ka}{$this->ten}",
            "{$this->hw_ka}{$this->hw_ten}" => "{$this->ka}{$this->ten}",
            "{$this->ka}{$this->hw_ten}" => "{$this->ka}{$this->ten}",
            "{$this->hw_ka}{$this->ten}" => "{$this->ka}{$this->ten}",
        );
            
        $i = -1;
        foreach($nfkd as $test_value => $test_result)
        {
            $i++;
            $decomp = Unicode::utf8_decompose($test_value, Unicode::UNICODE_DECOMPOSITION_COMPATIBILITY);
            if($decomp !== $test_result)
            {
                throw new UnicodeTestCaseFailure(
                    array(
                        "index" => $i,
                        "input_literal" => $test_value,
                        "input_hex" => Unicode::hexdump($test_value),
                        "expected_output" => $test_result,
                        "expected_output_hex" => Unicode::hexdump($test_result),
                        "actual_output" => $decomp,
                        "actual_output_hex" => Unicode::hexdump($decomp),
                    )
                );
            }
        }
    }
    
    /**
     * Test implementation of Unicode's NFC algorithm.
     *
     * NFC = Canonical Decomposition, followed by Canonical Recomposition.
     *
     * @throws UnicodeTestCaseFailure if decomposition result fails to match the correct value
     * 
     * @see http://www.unicode.org/reports/tr15/tr15-41.html Unicode Standard Annex 7.0 #15 Normalization Forms
     * 
     * @return void
     */
    public function test_nfc()
    {
        $nfc = array(
            "{$this->D_dot_above}" => "{$this->D_dot_above}",
            "{$this->D}{$this->dot_above}" => "{$this->D_dot_above}",
            "{$this->D_dot_below}{$this->dot_above}" => "{$this->D_dot_below}{$this->dot_above}",
            "{$this->D_dot_above}{$this->dot_below}" => "{$this->D_dot_below}{$this->dot_above}",
            "{$this->D}{$this->dot_above}{$this->dot_below}" => "{$this->D_dot_below}{$this->dot_above}",
            "{$this->D}{$this->dot_above}{$this->horn}{$this->dot_below}" => "{$this->D_dot_below}{$this->horn}{$this->dot_above}",
            "{$this->E_macron_grave}" => "{$this->E_macron_grave}",
            "{$this->E_macron}{$this->grave}" => "{$this->E_macron_grave}",
            "{$this->E_grave}{$this->macron}" => "{$this->E_grave}{$this->macron}",
            "{$this->angstrom}" => "{$this->A_ring}",
            "{$this->A_ring}" => "{$this->A_ring}",

            "{$this->A_diaeresis}ffin" => "{$this->A_diaeresis}ffin",
            "{$this->A_diaeresis}{$this->ffi_ligature}n" => "{$this->A_diaeresis}{$this->ffi_ligature}n",
            "Henry IV" => "Henry IV",
            "Henry {$this->IV}" => "Henry {$this->IV}",
            "{$this->ga}" => "{$this->ga}",
            "{$this->ka}{$this->ten}" => "{$this->ga}",
            "{$this->hw_ka}{$this->hw_ten}" => "{$this->hw_ka}{$this->hw_ten}",
            "{$this->ka}{$this->hw_ten}" => "{$this->ka}{$this->hw_ten}",
            "{$this->hw_ka}{$this->ten}" => "{$this->hw_ka}{$this->ten}",
        );
            
        $i = -1;
        foreach($nfc as $test_value => $test_result)
        {
            $i++;
            $decomp = Unicode::utf8_recompose(Unicode::utf8_decompose($test_value));
            if($decomp !== $test_result)
            {
                throw new UnicodeTestCaseFailure(
                    array(
                        "index" => $i,
                        "input_literal" => $test_value,
                        "input_hex" => Unicode::hexdump($test_value),
                        "expected_output" => $test_result,
                        "expected_output_hex" => Unicode::hexdump($test_result),
                        "actual_output" => $decomp,
                        "actual_output_hex" => Unicode::hexdump($decomp),
                    )
                );
            }
        }
    }
    
    /**
     * Test implementation of Unicode's NFKC algorithm.
     *
     * NFKC = Compatibility Decomposition, followed by Canonical Recomposition.
     *
     * @throws UnicodeTestCaseFailure if decomposition result fails to match the correct value
     * 
     * @see http://www.unicode.org/reports/tr15/tr15-41.html Unicode Standard Annex 7.0 #15 Normalization Forms
     * 
     * @return void
     */
    public function test_nfkc()
    {
        $nfkc = array(
            "{$this->D_dot_above}" => "{$this->D_dot_above}",
            "{$this->D}{$this->dot_above}" => "{$this->D_dot_above}",
            "{$this->D_dot_below}{$this->dot_above}" => "{$this->D_dot_below}{$this->dot_above}",
            "{$this->D_dot_above}{$this->dot_below}" => "{$this->D_dot_below}{$this->dot_above}",
            "{$this->D}{$this->dot_above}{$this->dot_below}" => "{$this->D_dot_below}{$this->dot_above}",
            "{$this->D}{$this->dot_above}{$this->horn}{$this->dot_below}" => "{$this->D_dot_below}{$this->horn}{$this->dot_above}",
            "{$this->E_macron_grave}" => "{$this->E_macron_grave}",
            "{$this->E_macron}{$this->grave}" => "{$this->E_macron_grave}",
            "{$this->E_grave}{$this->macron}" => "{$this->E_grave}{$this->macron}",
            "{$this->angstrom}" => "{$this->A_ring}",
            "{$this->A_ring}" => "{$this->A_ring}",

            "{$this->A_diaeresis}ffin" => "{$this->A_diaeresis}ffin",
            "{$this->A_diaeresis}{$this->ffi_ligature}n" => "{$this->A_diaeresis}ffin",
            "Henry IV" => "Henry IV",
            "Henry {$this->IV}" => "Henry IV",
            "{$this->ga}" => "{$this->ga}",
            "{$this->ka}{$this->ten}" => "{$this->ga}",
            "{$this->hw_ka}{$this->hw_ten}" => "{$this->ga}",
            "{$this->ka}{$this->hw_ten}" => "{$this->ga}",
            "{$this->hw_ka}{$this->ten}" => "{$this->ga}",
        );
            
        $i = -1;
        foreach($nfkc as $test_value => $test_result)
        {
            $i++;
            $decomp = Unicode::utf8_recompose(Unicode::utf8_decompose($test_value, Unicode::UNICODE_DECOMPOSITION_COMPATIBILITY), Unicode::UNICODE_DECOMPOSITION_COMPATIBILITY);
            if($decomp !== $test_result)
            {
                throw new UnicodeTestCaseFailure(
                    array(
                        "index" => $i,
                        "input_literal" => $test_value,
                        "input_hex" => Unicode::hexdump($test_value),
                        "expected_output" => $test_result,
                        "expected_output_hex" => Unicode::hexdump($test_result),
                        "actual_output" => $decomp,
                        "actual_output_hex" => Unicode::hexdump($decomp),
                    )
                );
            }
        }
    }
    
    /**
     * Test implementation of Unicode's Default Case Folding algorithm
     *
     * A casefolding algorithm generalized to Unicode's character set
     *
     * @throws UnicodeTestCaseFailure if decomposition result fails to match the correct value
     * 
     * @see http://www.unicode.org/versions/Unicode7.0.0/ch03.pdf The Unicode Standard, Chapter 3 Conformance, Section 13 Default Case Algorithms
     * 
     * @return void
     */
    public function test_case_fold()
    {
        $case = array(
            "D" => "d",
            "Ł" => "ł",
        );
        
        $i = -1;
        foreach($case as $test_value => $test_result)
        {
            $i++;
            $decomp = Unicode::casefold($test_value);
            if($decomp !== $test_result)
            {
                throw new UnicodeTestCaseFailure(
                    array(
                        "index" => $i,
                        "input_literal" => $test_value,
                        "input_hex" => Unicode::hexdump($test_value),
                        "expected_output" => $test_result,
                        "expected_output_hex" => Unicode::hexdump($test_result),
                        "actual_output" => $decomp,
                        "actual_output_hex" => Unicode::hexdump($decomp),
                    )
                );
            }
        }
    }
}
