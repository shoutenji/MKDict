<?php

namespace MKDict\Database\Exception;

use MKDict\Exception\FatalException;
use MKDict\Database\DBTableCreator;

class TableCreationFaliedException extends FatalException
{
    public $table;
    
    public function __construct(DBTableCreator $table)
    {
        $this->table = $table;
        parent::__construct(debug_backtrace());
    }
    
    public function get_message()
    {
        return "\n" . $this->colorize_text(ltrim(strrchr(__CLASS__, '\\'),'\\')) . "\n\nTable Object:\n" . print_r($this->table, true) . "\nTrace:\n" . print_r($this->getTrace(),true);
    }
}
