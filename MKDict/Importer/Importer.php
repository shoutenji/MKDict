<?php

namespace MKDict\Importer;

use MKDict\FileResource\FileInfo;
use MKDict\FileResource\GZFileResource;
use MKDict\FileResource\PlainTextFileResource;
use MKDict\FileResource\Url;
use MKDict\Security\Security;
use MKDict\FileResource\Exception\GZException\GZBadHeaderException;
use MKDict\Unicode\Unicode;
use MKDict\Unicode\Exception\InvalidUtf8Bytes;
use MKDict\DTD\DTD;
use MKDict\Database\DBConnection;
use MKDict\Database\DBTableCreator;
use MKDict\Database\Exception\DBError;
use MKDict\Logger\ImportLogger;

//todo to many rewind()'s for $jmdict_file, should rework this class to be handle 
class Importer
{
    public $db_conn;
    public $dtd;
    
    protected $jmdict_file;
    
    public function __construct()
    {
        global $config, $options;
        
        $this->db_conn = new DBConnection($config['dsn'], $config['db_user'], $config['db_pass']);
        $this->logger = new ImportLogger();
    }
    
    public function start_transation()
    {
        $this->db_conn->start_transaction();
    }
    
    public function roll_back()
    {
        $this->db_conn->roll_back();
    }
    
    public function import()
    {
        global $options, $config;
        
        if(!$options['local_copy'])
        {
            $this->download_jmdict();
        }
        else
        {
            $this->set_local_jmdict();
        }
        
        if($options['validate_crc32'])
        {
            //$this->validate_crc32();
        }
        
        if($options['validate_utf8'])
        {
            //$this->validate_utf8();
        }
        
        $this->jmdict_file->rewind();
        $this->dtd = new DTD($this->jmdict_file);
        $this->dtd->canonicalize();
        
        $previous_dtd = $this->get_previous_dtd();
        
        //first dtd ie first ever import
        if(false === $previous_dtd)
        {
            $this->dtd->dtd_version = 1;
            $this->dtd->dictionary_version = 1;
            $this->dtd->version_id = 1;
        }
        //new dtd
        else if($previous_dtd && !$dtd->is_equl($previous_dtd))
        {
            $this->dtd->dtd_version = $previous_dtd->dtd_version + 1;
            $this->dtd->dictionary_version = 1;
        }
        //same dtd, new dictionary release
        else
        {
            $this->dtd->dtd_version = $previous_dtd->dtd_version;
            $this->dtd->dictionary_version = $previous_dtd->dictionary_version + 1;
        }
        
        if($options['version_dictionary'])
        {
            $this->version_dictionary();
        }
        
        if($options['parse_dictionary'])
        {
            $this->parse_dictionary();
        }
    }
    
    protected function version_dictionary()
    {
        global $config;
        
        $this->db_conn->prepare("INSERT INTO ".$config['table_dict_version']." VALUES (:download_date, :dtd_raw, :dtd_canonical, :dtd_version, :dictionary_version, :version_id);");
        $this->db_conn->bindValue(':download_date', null, \PDO::PARAM_STR);
        $this->db_conn->bindValue(':dtd_raw', $this->dtd->dtd_raw, \PDO::PARAM_STR);
        $this->db_conn->bindValue(':dtd_canonical', $this->dtd->dtd_canonical, \PDO::PARAM_STR);
        $this->db_conn->bindValue(':dtd_version', $this->dtd->dtd_version, \PDO::PARAM_INT);
        $this->db_conn->bindValue(':dictionary_version', $this->dtd->dictionary_version, \PDO::PARAM_INT);
        $this->db_conn->bindValue(':version_id', null, \PDO::PARAM_INT);

        $this->db_conn->execute();
        $this->db_conn->query("SELECT LAST_INSERT_ID() AS version_id;");
        $this->dtd->version_id = $this->db_conn->fetch(\PDO::FETCH_ASSOC)['version_id'];
    }
    
    protected function get_versioned_dictionary_parser()
    {
        $parser_class_name = "MKDict\\v{$this->dtd->dtd_version}\\XML\\JMDictParser";
        $this->jmdict_file->rewind();
        return new $parser_class_name($this->jmdict_file, $this->dtd, $this->db_conn, $this->logger);
    }
    
    protected function parse_dictionary()
    {
        $parser = $this->get_versioned_dictionary_parser();
        
    }
    
    //todo can use pdo's db to object mapper
    protected function get_previous_dtd()
    {
        global $config;
        
        $this->db_conn->query("SELECT dtd_raw, dtd_canonical, dtd_version, dictionary_version, version_id FROM ".$config['table_dict_version']." ORDER BY version_id DESC LIMIT 1;");
        $dtd_results = $this->db_conn->fetch(\PDO::FETCH_ASSOC);
        
        if(empty($dtd_results))
        {
            return false;
        }
        else
        {
            $dtd = new DTD();
            $dtd->version_id = (int) $dtd_results['version_id'];
            $dtd->dtd_canonical = (int) $dtd_results['dtd_canonical'];
            $dtd->dtd_version = (int) $dtd_results['dtd_version'];
            $dtd->dictionary_version = (int) $dtd_results['dictionary_version'];
            $dtd->dtd_raw = $dtd_results['dtd_raw'];
            return $dtd;
        }
    }
    
    protected function validate_utf8()
    {
        global $config;
        
        $this->jmdict_file->rewind();
        if(false === Unicode::utf8_validate_file($this->jmdict_file))
        {
            throw new InvalidUtf8Bytes($this->jmdict_file->get_finfo());
        }
    }
    
    //validate the gzip file in accordance with http://www.ietf.org/rfc/rfc1952.txt
    //the crc algorithm below is lifted verbatim from PHP's C source
    protected function validate_crc32()
    {
        global $config;
        
        $crc32tab = array(
            0x00000000, 0x77073096, 0xee0e612c, 0x990951ba,
            0x076dc419, 0x706af48f, 0xe963a535, 0x9e6495a3,
            0x0edb8832, 0x79dcb8a4, 0xe0d5e91e, 0x97d2d988,
            0x09b64c2b, 0x7eb17cbd, 0xe7b82d07, 0x90bf1d91,
            0x1db71064, 0x6ab020f2, 0xf3b97148, 0x84be41de,
            0x1adad47d, 0x6ddde4eb, 0xf4d4b551, 0x83d385c7,
            0x136c9856, 0x646ba8c0, 0xfd62f97a, 0x8a65c9ec,
            0x14015c4f, 0x63066cd9, 0xfa0f3d63, 0x8d080df5,
            0x3b6e20c8, 0x4c69105e, 0xd56041e4, 0xa2677172,
            0x3c03e4d1, 0x4b04d447, 0xd20d85fd, 0xa50ab56b,
            0x35b5a8fa, 0x42b2986c, 0xdbbbc9d6, 0xacbcf940,
            0x32d86ce3, 0x45df5c75, 0xdcd60dcf, 0xabd13d59,
            0x26d930ac, 0x51de003a, 0xc8d75180, 0xbfd06116,
            0x21b4f4b5, 0x56b3c423, 0xcfba9599, 0xb8bda50f,
            0x2802b89e, 0x5f058808, 0xc60cd9b2, 0xb10be924,
            0x2f6f7c87, 0x58684c11, 0xc1611dab, 0xb6662d3d,
            0x76dc4190, 0x01db7106, 0x98d220bc, 0xefd5102a,
            0x71b18589, 0x06b6b51f, 0x9fbfe4a5, 0xe8b8d433,
            0x7807c9a2, 0x0f00f934, 0x9609a88e, 0xe10e9818,
            0x7f6a0dbb, 0x086d3d2d, 0x91646c97, 0xe6635c01,
            0x6b6b51f4, 0x1c6c6162, 0x856530d8, 0xf262004e,
            0x6c0695ed, 0x1b01a57b, 0x8208f4c1, 0xf50fc457,
            0x65b0d9c6, 0x12b7e950, 0x8bbeb8ea, 0xfcb9887c,
            0x62dd1ddf, 0x15da2d49, 0x8cd37cf3, 0xfbd44c65,
            0x4db26158, 0x3ab551ce, 0xa3bc0074, 0xd4bb30e2,
            0x4adfa541, 0x3dd895d7, 0xa4d1c46d, 0xd3d6f4fb,
            0x4369e96a, 0x346ed9fc, 0xad678846, 0xda60b8d0,
            0x44042d73, 0x33031de5, 0xaa0a4c5f, 0xdd0d7cc9,
            0x5005713c, 0x270241aa, 0xbe0b1010, 0xc90c2086,
            0x5768b525, 0x206f85b3, 0xb966d409, 0xce61e49f,
            0x5edef90e, 0x29d9c998, 0xb0d09822, 0xc7d7a8b4,
            0x59b33d17, 0x2eb40d81, 0xb7bd5c3b, 0xc0ba6cad,
            0xedb88320, 0x9abfb3b6, 0x03b6e20c, 0x74b1d29a,
            0xead54739, 0x9dd277af, 0x04db2615, 0x73dc1683,
            0xe3630b12, 0x94643b84, 0x0d6d6a3e, 0x7a6a5aa8,
            0xe40ecf0b, 0x9309ff9d, 0x0a00ae27, 0x7d079eb1,
            0xf00f9344, 0x8708a3d2, 0x1e01f268, 0x6906c2fe,
            0xf762575d, 0x806567cb, 0x196c3671, 0x6e6b06e7,
            0xfed41b76, 0x89d32be0, 0x10da7a5a, 0x67dd4acc,
            0xf9b9df6f, 0x8ebeeff9, 0x17b7be43, 0x60b08ed5,
            0xd6d6a3e8, 0xa1d1937e, 0x38d8c2c4, 0x4fdff252,
            0xd1bb67f1, 0xa6bc5767, 0x3fb506dd, 0x48b2364b,
            0xd80d2bda, 0xaf0a1b4c, 0x36034af6, 0x41047a60,
            0xdf60efc3, 0xa867df55, 0x316e8eef, 0x4669be79,
            0xcb61b38c, 0xbc66831a, 0x256fd2a0, 0x5268e236,
            0xcc0c7795, 0xbb0b4703, 0x220216b9, 0x5505262f,
            0xc5ba3bbe, 0xb2bd0b28, 0x2bb45a92, 0x5cb36a04,
            0xc2d7ffa7, 0xb5d0cf31, 0x2cd99e8b, 0x5bdeae1d,
            0x9b64c2b0, 0xec63f226, 0x756aa39c, 0x026d930a,
            0x9c0906a9, 0xeb0e363f, 0x72076785, 0x05005713,
            0x95bf4a82, 0xe2b87a14, 0x7bb12bae, 0x0cb61b38,
            0x92d28e9b, 0xe5d5be0d, 0x7cdcefb7, 0x0bdbdf21,
            0x86d3d2d4, 0xf1d4e242, 0x68ddb3f8, 0x1fda836e,
            0x81be16cd, 0xf6b9265b, 0x6fb077e1, 0x18b74777,
            0x88085ae6, 0xff0f6a70, 0x66063bca, 0x11010b5c,
            0x8f659eff, 0xf862ae69, 0x616bffd3, 0x166ccf45,
            0xa00ae278, 0xd70dd2ee, 0x4e048354, 0x3903b3c2,
            0xa7672661, 0xd06016f7, 0x4969474d, 0x3e6e77db,
            0xaed16a4a, 0xd9d65adc, 0x40df0b66, 0x37d83bf0,
            0xa9bcae53, 0xdebb9ec5, 0x47b2cf7f, 0x30b5ffe9,
            0xbdbdf21c, 0xcabac28a, 0x53b39330, 0x24b4a3a6,
            0xbad03605, 0xcdd70693, 0x54de5729, 0x23d967bf,
            0xb3667a2e, 0xc4614ab8, 0x5d681b02, 0x2a6f2b94,
            0xb40bbe37, 0xc30c8ea1, 0x5a05df1b, 0x2d02ef8d,
        );
        
        //we have to read regular bytes from the gzip file header
        $jmdict_local_file_plaintext = new PlainTextFileResource($this->jmdict_file->get_finfo());
        $jmdict_local_file_plaintext->open();
        $header = $jmdict_local_file_plaintext->read(10);
        
        if(ord($header[0]) != 0x1F && ord($header[1]) != 0x8B)
        {
            throw new GZBadHeaderException($jmdict_local_file_plaintext->get_finfo(), "Invalid GZip ID");
        }
        
        if(ord($header[2]) != 8)
        {
            throw new GZBadHeaderException($jmdict_local_file_plaintext->get_finfo(), "Invalid compression method");
        }
        
        /*
        //the rest of the gzip file header if this information is wanted later
        $flag_byte = ord($header[3]);

        //the FLG.FEXTRA bit
        if(($flag_byte & 0x04 ) !== 0)
        {
            $xlen = $jmdict_local_file_plaintext->read(2);
            $jmdict_local_file_plaintext->seek(ord($xlen)+1, \SEEK_CUR);
        }
        
        //the FLG.FNAME bit
        if(($flag_byte & 0x08 ) !== 0)
        {
            while(!$jmdict_local_file_plaintext->feof())
            {
                if("\x00" === $jmdict_local_file_plaintext->read(1))
                {
                    break;
                }
            }
        }
        
        //the FLG.FCOMMENT bit
        if(($flag_byte & 0x10 ) !== 0)
        {
            while(!$jmdict_local_file_plaintext->feof())
            {
                if("\x00" === $jmdict_local_file_plaintext->read(1))
                {
                    break;
                }
            }
        }
        
        //the FLG.FHCRC bit
        if(($flag_byte & 0x02 ) !== 0)
        {
            $jmdict_local_file_plaintext->seek(3, \SEEK_CUR);
        }
        */
        
        $jmdict_local_file_plaintext->seek(-8, \SEEK_END);
        $given_crc = $jmdict_local_file_plaintext->read(4);
        $given_crc = hexdec(bin2hex(strrev($given_crc)));
        
        //calculate the crc32
        $this->jmdict_file->rewind();
        $crc_register = 0 ^ 0xFFFFFFFF;
        while(!$this->jmdict_file->feof())
        {
            $message_bytes = $this->jmdict_file->read($config['GZ_buffer_rw_size']);
            for($i=0, $len=strlen($message_bytes); $i<$len; $i++)
            {
                $crc_register = (($crc_register >> 8) & 0x00FFFFFF) ^ $crc32tab[($crc_register ^ ord($message_bytes[$i])) & 0xFF ];
            }
        }
        $computed_crc = $crc_register ^ 0xFFFFFFFF;
        
        if($given_crc !== $computed_crc)
        {
            throw new GZBadHeaderException($this->jmdict_file->get_finfo(), "CRC32 mismatch");
        }
        unset($crc32tab);
    }
    
    protected function set_local_jmdict()
    {
        global $options, $config;
        
        if($options['sample_gz_file'])
        {
            $jmdict_local_filename = $options['sample_gz_file'];
        }
        else
        {
            $jmdict_local_filename = $options['gz_file'];
        }

        $jmdict_sample_gz_finfo = new FileInfo($jmdict_local_filename, $config['data_dir']);
        $jmdict_sample_gz_finfo->set_mode("rb");
        $this->jmdict_file = new GZFileResource($jmdict_sample_gz_finfo);
        $this->jmdict_file->open();
    }
    
    protected function download_jmdict()
    {
        global $options, $config;
        
        $jmdict_remote_finfo = new FileInfo();
        $jmdict_remote_finfo->set_url(new Url("$config[EDRDG_domain]/$config[EDRDG_path]/$config[JMDict_filename]"));
        $jmdict_remote_finfo->set_mode("rb");
        $jmdict_remote_finfo->set_stream_context(array(
            'http' => array(
                    'method'    =>  'GET',
                    'follow_location'   =>  0,
                    'timeout'   =>  120,
            )
        ));
        $jmdict_remote_file = new GZFileResource($jmdict_remote_finfo);
        $jmdict_remote_file->open();

        //create the local binary file we're going to write the downloaded bytes into
        $jmdict_local_finfo = new FileInfo(Security::weak_random_string(10) . '_' . time() . '.gz', $config['data_dir']);
        $jmdict_local_finfo->set_mode('w+b');
        $jmdict_local_file = new GZFileResource($jmdict_local_finfo);
        $jmdict_local_file->open();
        
        $jmdict_local_file->download_from($jmdict_remote_file);
        
        $jmdict_local_file->rewind();
        $this->jmdict_file = $jmdict_local_file;
    }
}
