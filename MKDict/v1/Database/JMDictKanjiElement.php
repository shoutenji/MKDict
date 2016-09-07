<?php

namespace MKDict\v1\Database;

use MKDict\v1\Database\JMDictStringElement;
use MKDict\v1\Database\Comparable;

/**
 * Class for representing a JMDict XML kanji element
 * 
 * @author Taylor B <taylorbrontario@riseup.net>
 */
class JMDictKanjiElement extends JMDictStringElement implements Comparable
{
    /**
     * Test for equality
     * 
     * @param \MKDict\v1\Database\Comparable $kanji1
     * @param \MKDict\v1\Database\Comparable $kanji2
     * 
     * @return bool True if equal, false otherwise
     */
    public static function is_equal(Comparable $kanji1, Comparable $kanji2)
    {
        return parent::is_equal($kanji1, $kanji2);
    }
    
    /**
     * Test for equality
     * 
     * @param \MKDict\v1\Database\Comparable $other
     * 
     * @return bool True if equal, false otherwise
     */
    public function is_equal_to(Comparable $other)
    {
        return self::is_equal($this, $other);
    }
}
