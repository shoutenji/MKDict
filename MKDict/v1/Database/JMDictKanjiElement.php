<?php

namespace MKDict\v1\Database;

use MKDict\v1\Database\JMDictStringElement;
use MKDict\v1\Database\Comparable;

class JMDictKanjiElement extends JMDictStringElement implements Comparable
{
    public static function is_equal(Comparable $kanji1, Comparable$kanji2)
    {
        return parent::is_equal($kanji1, $kanji2);
    }
    
    public function is_equal_to(Comparable $other)
    {
        return self::is_equal($this, $other);
    }
}
