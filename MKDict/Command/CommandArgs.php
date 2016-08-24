<?php

namespace MKDict\Command;

class CommandArgs extends \ArrayObject
{
    protected $command_args;
    
    public function __construct($command_args)
    {
        $this->command_args = array();
        parent::__construct($this->command_args);
        
        foreach($command_args as $arg)
        {
            if(preg_match("/--sample-gz-file=(.*)\.gz/ixsm", $arg))
            {
                $arg_tokens = explode("=", $arg);
                $this["sample_gz_file"] = trim($arg_tokens[1]);
            }
            else if(preg_match("/--gz-file=(.*)\.gz/ixsm", $arg))
            {
                $arg_tokens = explode("=", $arg);
                $this["gz_file"] = trim($arg_tokens[1]);
            }
            else if(preg_match("/--export-version=(\d+)/ixsm", $arg))
            {
                $arg_tokens = explode("=", $arg);
                $this["export_version"] = trim($arg_tokens[1]);
            }
            else
            {
                switch($arg)
                {
                    case "--bash-output":
                        $this["bo_output"] = true;
                        break;

                    case "--validate-utf8":
                        $this['validate_utf8'] = true;
                        break;

                    case "--validate-crc32":
                        $this['validate_crc32'] = true;
                        break;

                    case "--version-dictionary":
                        $this['version_dictionary'] = true;
                        break;

                    case "--parse-dictionary":
                        $this['parse_dictionary'] = true;
                        break;

                    case "--finalize-only":
                        $this['finalize_only'] = true;
                        break;

                    case "--generate-utf-data":
                        $this['generate_utf_data'] = true;
                        break;

                    case "--utf-tests":
                        $this['utf_tests'] = true;
                        break;

                    case "--test-db":
                        $this['test_db'] = true;
                        break;

                    case "--create-db":
                        $this['create_db'] = true;
                        break;

                    case "--local-copy":
                        $this['local_copy'] = true;
                        break;

                    case "--debug-version":
                        $this['debug_version'] = true;
                        break;

                    case "--with-rollback":
                        $this['with_rollback'] = true;
                        break;

                    default:
                        //die("Error: Unrecognized command line argument: $arg");
                        break;
                }
            }
        }
    }
}