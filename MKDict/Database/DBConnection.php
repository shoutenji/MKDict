<?php

namespace MKDict\Database;

use MKDict\Database\Exception\DBConnectionError;
use MKDict\Database\Exception\DBError;
use MKDict\Security\Security;

/**
 * A PDO wrapper
 * 
 * @author Taylor B <taylorbrontario@riseup.net>
 */
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

    /**
     * Constructor
     * 
     * @param string $dsn DSN string
     * @param string $user DB username
     * @param string $pass DB password
     * 
     * @throws DBConnectionError
     */
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
            throw new DBConnectionError($e->getMessage());
        }
    }

    /**
     * Start a MYSQL transaction
     * 
     * @throws DBConnectionError
     */
    public function start_transaction()
    {
        if(!$this->pdo)
        {
            throw new DBConnectionError(__METHOD__." was called but no database connection has been established.");
        }
        $this->pdo->beginTransaction();
    }

    /**
     * Roll back transaction
     * 
     * @throws DBConnectionError
     */
    public function roll_back()
    {
        if(!$this->pdo)
        {
            throw new DBConnectionError( __METHOD__." was called but no database connection has been established.");
        }
        $this->pdo->rollBack();
    }

    /**
     * Check if a field is null
     * 
     * @param string $field
     * 
     * @return string A SQL boolean expression checking if the field is null
     */
    public function null_check(string $field)
    {
        return " $field IS NULL "; 
    }
    
    /**
     * Safely flatten an array for use in an IN() clause
     * 
     * @param array $ary
     * @param string $type The type to coerce too
     * 
     * @return string The flattened array
     */
    public static function flatten_array(array $ary, string $type = "int")
    {
        return Security::flatten_array($ary, $type);
    }

    /**
     * Does a version check
     * 
     * @param type $column_prefix
     * @return string The checked versioned
     * 
     * @todo store the column name in a class property and autobind the values before execution, that way the user doesn't have to take care of binding
     */
    public function version_check(string $column_prefix = "")
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

    /**
     * Wrapper for PDOStatement::debugDumpParams()
     * 
     * @return array|boolean The result of PDOStatement::debugDumpParams() or false otherwise
     */
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

    /**
     * Get a PDO attribute
     * 
     * @param string $attr_name
     * @return mixed
     */
    public function getAttribute(string $attr_name)
    {
        return $this->pdo->getAttribute($attr_name);
    }

    /**
     * Set a PDO attribute
     * 
     * @param string $attr_name
     * @param string $attr_value
     */
    public function setAttribute(string $attr_name, string $attr_value)
    {
        $this->pdo->setAttribute($attr_name, $attr_value);
    }

    /**
     * Wrapper for PDO::exec()
     * 
     * @param string $stmnt
     * @throws DBError
     */
    public function exec(string $stmnt)
    {
        if(false === $this->pdo->exec($stmnt))
        {
            throw new DBError(debug_backtrace(), $stmnt);
        }
        $this->status = $this::MK_PDO_EXED;
    }

    /**
     * Wrapper for PDO::query()
     * 
     * @param string $stmnt
     * @throws DBError
     */
    public function query(string $stmnt)
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

    /**
     * Used for maintaining a valid internal state
     * 
     * @param int $status_flag
     * @return type
     */
    protected function status_is(int $status_flag)
    {
        return (bool) ($this->status & $status_flag);
    }

    /**
     * Wrapper for PDOStatement::execute()
     * 
     * @param array $params
     * @throws DBError
     */
    public function execute(array $params = null)
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

    /**
     * Wrapper for PDOStatement::fetchAll()
     * 
     * @param int $fetchstyle
     * 
     * @return array
     * 
     * @throws DBError
     */
    public function fetchAll(int $fetchstyle)
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


    /**
     * Wrapper for PDOStatement::fetch()
     * 
     * @param int $fetchstyle
     * 
     * @return array
     * 
     * @throws DBError
     */
    public function fetch(int $fetchstyle)
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

    /**
     * Wrapper for PDOStatement::prepare()
     * 
     * @param string $stmnt
     * 
     * @throws DBError
     */
    public function prepare(string $stmnt)
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

    /**
     * Wrapper for PDOStatement::bindValue()
     * 
     * @param string $parameter
     * @param mixed $value
     * @param int $data_type
     * 
     * @throws DBError
     */
    public function bindValue(string $parameter, $value, int $data_type)
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

    /**
     * Wrapper for PDOStatement::bindParam()
     * 
     * @param string $parameter
     * @param mixed $value
     * @param int $data_type
     * 
     * @throws DBError
     */
    public function bindParam(string $parameter, &$variable, int $data_type)
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

    /**
     * Wrapper for PDO::errorCode()
     * 
     * @return mixed
     */
    public function error_code()
    {
        return $this->pdo->errorCode();
    }

}
