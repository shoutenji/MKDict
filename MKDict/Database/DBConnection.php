<?php

namespace MKDict\Database;

use MKDict\Database\Exception\DBConnectionError;
use MKDict\Database\Exception\DBError;

class DBConnection
{
    protected $pdo, $statement;
    protected $status;

    const MK_PDO_PREPARED = 1;
    const MK_PDO_VALUE_BINDED = 2;
    const MK_PDO_EXECUTED = 4;
    const MK_PDO_FETCHED = 8;
    const MK_PDO_QUERIED = 16;
    const MK_PDO_EXED = 32;

    public function __construct(string $dsn, string $user, string $pass)
    {
        try
        {
            $this->pdo = new \PDO($dsn, $user, $pass);
            $db_selected_stmtn = $this->pdo->query("SELECT DATABASE() AS DB;");
            $db_selected_result = $db_selected_stmtn->fetch(\PDO::FETCH_ASSOC);

            if(!isset($db_selected_result['DB']))
            {
                throw new DBConnectionError("SQLSTATE No database selected for connection");
            }

            $this->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
            $this->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->setAttribute(\PDO::ATTR_CASE, \PDO::CASE_NATURAL);
            $this->exec("SET character_set_server=utf8mb4;");
            $this->exec("SET NAMES 'utf8mb4';");
        }
        catch(\PDOException $e)
        {
            throw new DBConnectionError($dsn, $user, $e->getMessage());
        }
    }

    public function start_transaction()
    {
        $this->pdo->beginTransaction();
    }

    public function roll_back()
    {
        $this->pdo->rollBack();
    }

    public function null_check($field)
    {
        return " $field IS NULL "; 
    }

    //TODO this is a sloppy way to implement a version check because it still requires a bindValue() call afterwords
    //TODO if you aren't going to change this, at least write a comment to explain what placeholders need to be bound
    //TODO you could return an array with the first index the string, and the second index the param names that need to be bound
    //if you use a column prefix you must bind the appropriate values. see JMDictDB::k_and_r_search()
    public function version_check($column_prefix = "")
    {
        $placeholder_prefix = "";
        if(!empty($column_prefix))
        {
            $placeholder_prefix = "{$column_prefix}_";
            $column_prefix = "{$column_prefix}.";
        }

        //don't remove trailing spaces
        //note: the parameter name is parameterized
        return " ( " . $this->null_check("{$column_prefix}version_removed_id") . " OR {$column_prefix}version_removed_id>:{$placeholder_prefix}version_removed_id ) AND ( {$column_prefix}version_added_id<=:{$placeholder_prefix}version_added_id) ";
    }

    public function debugDumpParams()
    {
        if(!empty($this->statement))
        {
            ob_start();
            $this->statement->debugDumpParams();
            $ob_contents = ob_get_contents();
            ob_end_clean();
            return $ob_contents;
        }
        else
        {
            return false;
        }
    }

    public function getAttribute($attr_name)
    {
        return $this->pdo->getAttribute($attr_name);
    }

    public function setAttribute($attr_name, $attr_value)
    {
        $this->pdo->setAttribute($attr_name, $attr_value);
    }

    public function exec($stmnt)
    {
        if(false === $this->pdo->exec($stmnt))
        {
            throw new DBError(debug_backtrace(), $stmnt);
        }
        $this->status = $this::MK_PDO_EXED;
    }

    public function query($stmnt)
    {
        if(false === $result = $this->pdo->query($stmnt))
        {
            throw new DBError(debug_backtrace(), $stmnt);
        }
        else
        {
            $this->status = $this::MK_PDO_QUERIED;
            $this->statement =  $result;
        }
    }

    protected function status_is($status_flag)
    {
        return (bool) ($this->status & $status_flag);
    }

    public function execute($params = null)
    {
        global $options;
        
        if($options['debug_version'])
        {
            if( !($this->status_is($this::MK_PDO_PREPARED) || $this->status_is($this::MK_PDO_VALUE_BINDED)) )
            {
                throw new DBError(debug_backtrace(), __CLASS__." context error: ".__METHOD__."() was not preceeded by a preapre() or bindValue() call.");
            }
        }

        if(false === $this->statement->execute($params))
        {
            throw new DBError(debug_backtrace(), print_r($this->error_code(),true));
        }
        $this->status = $this::MK_PDO_EXECUTED;
    }

    public function fetchAll($fetchstyle)
    {
        global $options;
        
        if($options['debug_version'])
        {
            if( !($this->status_is($this::MK_PDO_EXECUTED) || $this->status_is($this::MK_PDO_QUERIED)) )
            {
                throw new DBError(debug_backtrace(), __CLASS__." context error: ".__METHOD__."() was not preceeded by an execute() or query() call.");
            }
        }
        
        $results = $this->statement->fetchAll($fetchstyle);

        if(is_null($results))
        {
            throw new DBError(debug_backtrace(), $this->error_code());
        }
        else
        {
            $this->status = $this::MK_PDO_FETCHED;
            return $results;
        }
    }


    public function fetch($fetchstyle)
    {
        global $options;
        
        if($options['debug_version'])
        {
            if( !($this->status_is($this::MK_PDO_EXECUTED) || $this->status_is($this::MK_PDO_QUERIED)) )
            {
                throw new DBError(debug_backtrace(), __CLASS__." context error: ".__METHOD__."() was not preceeded by an execute() or query() call.");
            }
        }
        
        $result = $this->statement->fetch($fetchstyle);

        if(is_null($result))
        {
            throw new DBError(debug_backtrace(),$this->error_code());
        }
        else
        {
            $this->status = $this::MK_PDO_FETCHED;
            return $result;
        }
    }

    public function prepare($stmnt)
    {
        try
        {
            $this->statement =  $this->pdo->prepare($stmnt);
            $this->status = $this::MK_PDO_PREPARED;
        }
        catch(\PDOException $e)
        {
            throw new DBError(debug_backtrace(), $e->getMessage());
        }
    }

    public function bindValue($parameter, $value, $data_type)
    {
        global $options;
        
        if($options['debug_version'])
        {
            if( !($this->status_is($this::MK_PDO_PREPARED) || $this->status_is($this::MK_PDO_VALUE_BINDED)) )
            {
                throw new DBError(debug_backtrace(), __CLASS__." context error: ".__METHOD__."() was not preceeded by an prepare() call.");
            }
        }

        if(false === $this->statement->bindValue($parameter, $value, $data_type))
        {
            throw new DBError(debug_backtrace(),"parameter = '$parameter', attempted bind = '$value'");
        }
        $this->status = $this::MK_PDO_VALUE_BINDED;
    }

    public function bindParam($parameter, &$variable, $data_type)
    {
        global $options;
        
        if($options['debug_version'])
        {
            if( !($this->status_is($this::MK_PDO_PREPARED) || $this->status_is($this::MK_PDO_VALUE_BINDED)) )
            {
                throw new DBError(debug_backtrace(),__CLASS__." context error: ".__METHOD__."() was not preceeded by an prepare() call.");
            }
        }

        if(false === $this->statement->bindParam($parameter, $variable, $data_type))
        {
            throw new DBError(debug_backtrace(),"parameter = '$parameter', attempted bind = '$value'");
        }
        $this->status = $this::MK_PDO_VALUE_BINDED;
    }

    public function error_code()
    {
        return $this->pdo->errorCode();
    }

}
