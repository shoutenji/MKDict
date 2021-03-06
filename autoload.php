<?php

function my_autoload($class)
{
    $base_dir = __DIR__;
    
    $class_path = $base_dir;
    $names = explode("\\", $class);
    
    /*
    if(array_shift($names) !== "MKDict")
    {
        throw new Exception("Invalid namespace: $class");
    }
    */
    
    $class_name = array_pop($names);
    
    foreach($names as $name)
    {
        $class_path .= "/$name";
    }
    
    if(!@file_exists("$class_path/$class_name.php"))
    {
        die("File does not exist: $class_path/$class_name.php\n".print_r(debug_backtrace(),true));
    }
    
    require_once "$class_path/$class_name.php";
}

spl_autoload_register("my_autoload");
