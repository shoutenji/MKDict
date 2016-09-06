<?php

namespace MKDict\Database;

use MKDict\Database\Exception\DBConnectionError;
use MKDict\Database\DBConnection;

/**
 * A convenienve class for creating tables in the db
 * 
 * @author Taylor B <taylorbrontario@riseup.net>
 */
class DBTableCreator
{
    protected $statement, $tablename, $engine_type, $columns, $connection, $table_error;
        
    const DEFAULT_ENGINE_TYPE = "INNODB";

    /**
     * Constructor
     * 
     * @param DBConnection $db_conn
     * @param string $tablename
     * @param string $engine_type
     */
    public function __construct(DBConnection $db_conn, string $tablename, string $engine_type = self::DEFAULT_ENGINE_TYPE)
    {
        $this->connection = $db_conn;
        $this->tablename = $tablename;
        $this->engine_type = $engine_type;
        $this->columns = array();
    }

    /**
     * Add a column to the table
     * 
     * @param string $column_name
     * @param string $data_type
     * @param string $default
     * @param string $nullability
     * @param string $keyability
     * @param string $auto_increment
     */
    public function add_column(string $column_name, string $data_type, string $default = "", string $nullability = "NOT NULL", string $keyability = "", string $auto_increment = "")
    {
        $this->columns[] = array('column_name' => $column_name, 'data_type' => $data_type, 'default' => $default, 'nullability' => $nullability, 'keyability' => $keyability, 'auto_increment' => $auto_increment);
    }

    /**
     * Add a foriegn key to the table
     * 
     * @param string $column_name
     * @param string $referenced_table
     * @param string $referenced_column_name
     */
    public function add_foriegn_key(string $column_name, string $referenced_table, string $referenced_column_name)
    {
        $this->columns[] = array('foreign_key' => true, 'column_name' => $column_name, 'referenced_table' => $referenced_table, 'referenced_column_name' => $referenced_column_name);
    }

    /**
     * Add a key to the table
     * 
     * @param string $index_type
     * @param string $column_names
     */
    public function add_key(string $index_type, string $column_names)
    {
        if(is_array($column_names))
        {
            $column_names = implode(", ", $column_names);
        }
        $this->columns[] = array('index_type' => $index_type, 'column_name' => $column_names);
    }

    /**
     * Create the table
     * 
     * @throws DBConnectionError
     */
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

    /**
     * 
     */
    public function check_table_error()
    {
    }
}
