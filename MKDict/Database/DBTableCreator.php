<?php

namespace MKDict\Database;

use MKDict\Database\Exception\DBConnectionError;
use MKDict\Database\DBConnection;

class DBTableCreator
{
    protected $statement, $tablename, $engine_type, $columns, $connection, $table_error;
        
    const DEFAULT_ENGINE_TYPE = "INNODB";

    public function __construct(DBConnection $db_conn, string $tablename, string $engine_type = self::DEFAULT_ENGINE_TYPE)
    {
        $this->connection = $db_conn;
        $this->tablename = $tablename;
        $this->engine_type = $engine_type;
        $this->columns = array();
    }

    public function add_column($column_name, $data_type, $default = "", $nullability = "NOT NULL", $keyability = "", $auto_increment = "")
    {
        $this->columns[] = array('column_name' => $column_name, 'data_type' => $data_type, 'default' => $default, 'nullability' => $nullability, 'keyability' => $keyability, 'auto_increment' => $auto_increment);
    }

    public function add_foriegn_key($column_name, $referenced_table, $referenced_column_name)
    {
        $this->columns[] = array('foreign_key' => true, 'column_name' => $column_name, 'referenced_table' => $referenced_table, 'referenced_column_name' => $referenced_column_name);
    }

    public function add_key($index_type, $column_names)
    {
        if(is_array($column_names))
        {
            $column_names = implode(", ", $column_names);
        }
        $this->columns[] = array('index_type' => $index_type, 'column_name' => $column_names);
    }

    public function create()
    {
        if(empty($this->connection))
        {
            throw new DBConnectionError(debug_backtrace());
        }

        $this->statement = "CREATE TABLE $this->tablename (\n";
        while(list(,$column) = each($this->columns))
        {
            if(isset($column['foreign_key']))
            {
                $this->statement .= "   FOREIGN KEY ($column[column_name]) REFERENCES $column[referenced_table] ($column[referenced_column_name])";
            }
            else if(isset($column['index_type']))
            {
                $this->statement .= "   $column[index_type] ($column[column_name])";
            }
            else
            {
                $this->statement .= "   $column[column_name] $column[data_type] $column[nullability] $column[default] $column[auto_increment] $column[keyability]";
            }
            if(current($this->columns))
            {
                $this->statement .= ",\n";
            }
        }
        $this->statement .= "\n) ENGINE=$this->engine_type;";

        $this->connection->exec("SET FOREIGN_KEY_CHECKS=0;");
        $this->connection->exec("DROP TABLE IF EXISTS $this->tablename;");
        $this->connection->exec("SET FOREIGN_KEY_CHECKS=1;");

        $this->connection->exec($this->statement);

        $this->table_error = $this->connection->error_code();
        //$this->check_table_error();
    }

    public function check_table_error()
    {
    }
}
