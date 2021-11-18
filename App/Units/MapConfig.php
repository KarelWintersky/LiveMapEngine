<?php

namespace Livemap\Units;

use Arris\Path;
use Exception;

class MapConfig
{
    private $map_alias;
    /**
     * @var string
     */
    private $json_config_filename;
    /**
     * @var mixed|string
     */
    private $config_type;
    
    private $config;
    /**
     * @var bool
     */
    private $error;
    /**
     * @var string
     */
    private $error_message;
    
    public function __construct($map_alias, $mode = 'file')
    {
        if (empty($map_alias)) {
            throw new \RuntimeException( "[JS Builder] Map alias not defined", 1 );
        }
        
        $this->map_alias = $map_alias;
        $this->json_config_filename = Path::create( getenv('PATH.STORAGE'))->join($this->map_alias)->joinName('index.json')->toString();
        $this->config_type = $mode;
    }
    
    public function loadConfig(){
        
        switch ($this->config_type) {
            case 'file' : {
                $this->loadConfig_File();
                break;
            }
            case 'mysql': {
                $this->loadConfig_MySQL();
                break;
            }
        }
        
        if ($this->error) {
            die( $this->error_message );
        }
    }
    
    public function getConfig(){
        return $this->config;
    }
    
    private function loadConfig_File() {
        try {
            if (!is_file($this->json_config_filename)) {
                throw new \Exception( "[JS Builder] {$this->json_config_filename} not found", 2 );
            }
            
            $json_config_content = file_get_contents( $this->json_config_filename );
            
            if (!$json_config_content) {
                throw new \Exception( "[JS Builer] Can't get content of {$this->json_config_filename} file." );
            }
            
            $json = json_decode( $json_config_content );
            
            if (!$json) {
                throw new Exception( "[JS Builder] {$this->json_config_filename} json file is invalid", 3 );
            }
            
            $this->config = $json;
            
        } catch (\Exception $e) {
            $this->error = TRUE;
            $this->error_message = json_last_error_msg();
        }
    }
    
    private function loadConfig_MySQL()
    {
    
    }
    
    
}