<?php

namespace Livemap\Units;

use Arris\Entity\Result;
use Arris\Path;
use Psr\Log\LoggerInterface;

class MapConfig extends \Livemap\AbstractClass
{
    /**
     * @var string
     */
    private string $json_config_filename;

    /**
     * @var string
     */
    private $map_id;

    /**
     * @var string
     */
    private $config_type;

    /**
     * @var \stdClass
     */
    private $config;

    public function __construct($map_id, $mode = 'file', $options = [], LoggerInterface $logger = null)
    {
        parent::__construct($options, $logger);

        if (empty($map_id)) {
            throw new \RuntimeException( "[JS Builder] Map alias not defined", 1 );
        }

        $this->map_id = $map_id;
        $this->json_config_filename =
            Path::create( config('path.storage') )
                ->join($this->map_id)
                ->joinName('index.json')
                ->toString();
        $this->config_type = $mode;

        $this->config = new Result();
    }

    public function loadConfig():self
    {
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

        return $this;
    }

    public function getConfig():Result
    {
        return $this->config;
    }

    private function loadConfig_File() {
        try {
            if (!is_file($this->json_config_filename)) {
                throw new \RuntimeException( "[JS Builder] {$this->json_config_filename} not found", 2 );
            }

            $json_config_content = file_get_contents( $this->json_config_filename );

            if (!$json_config_content) {
                throw new \RuntimeException( "[JS Builer] Can't get content of {$this->json_config_filename} file." );
            }

            $json = json_decode( $json_config_content );

            if (!$json) {
                throw new \RuntimeException( "[JS Builder] {$this->json_config_filename} json file is invalid", 3 );
            }

            $this->config->set('json', $json);

        } catch (\RuntimeException $e) {
            $this->config->error($e->getMessage());
            // $this->error_message = json_last_error_msg();
        }
    }

    private function loadConfig_MySQL()
    {

    }



}