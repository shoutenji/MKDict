<?php

namespace MKDict\v1\Database;

/**
 * Interface Comparable
 * 
 * @author Taylor B <taylorbrontario@riseup.net>
 */
interface Comparable
{
    /**
     * Test for equality
     * 
     * @param \MKDict\v1\Database\Comparable $instance1
     * @param \MKDict\v1\Database\Comparable $instance2
     * 
     * @return bool True if equal, false otherwise
     * 
     * @todo change name to are_equal()
     */
    public static function is_equal(Comparable $instance1, Comparable $instance2);
    
    /**
     * Test for equality
     * 
     * @param \MKDict\v1\Database\Comparable $other
     * 
     * @return bool True if equal, false otherwise
     */
    public function is_equal_to(Comparable $other);
}
