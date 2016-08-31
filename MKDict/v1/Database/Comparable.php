<?php

namespace MKDict\v1\Database;

interface Comparable
{
    public static function is_equal(Comparable $instance1, Comparable $instance2);
    
    public function is_equal_to(Comparable $other);
}
