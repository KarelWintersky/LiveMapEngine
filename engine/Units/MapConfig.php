<?php

namespace Livemap\Units;

use Arris\Path;
use ColinODell\Json5\SyntaxError;
use Psr\Log\LoggerInterface;

class MapConfig extends \Livemap\AbstractClass
{
    /**
     * @var string
     */
    private string $json_config_filename;

    /**
     * Тип файла конфига. Варианты:
     * json
     * json5
     *
     * @var string
     */
    private string $json_config_type;

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
    public $json;

    /**
     * @var bool
     */
    public bool $error = false;

    /**
     * @var string
     */
    public string $error_message = '';

    public function __construct($map_id, $mode = 'file', $options = [], LoggerInterface $logger = null)
    {
        parent::__construct($options, $logger);

        if (empty($map_id)) {
            throw new \RuntimeException( "[JS Builder] Map alias not defined", 1 );
        }

        $this->map_id = $map_id;
        $this->config_type = $mode;

        $fn_path = Path::create( config('path.storage') )->join($this->map_id);

        $fn = $fn_path->joinName('index.json5')->toString();

        if (is_readable($fn)) {
            $this->json_config_filename = $fn;
            $this->json_config_type = 'json5';
            return;
        }

        $fn = $fn_path->joinName('index.json')->toString();

        if (is_readable($fn)) {
            $this->json_config_filename = $fn;
            $this->json_config_type = 'json';
            return;
        }

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

    public function getConfig():\stdClass
    {
        return $this->json;
    }

    /**
     * @throws SyntaxError
     */
    private function loadConfig_File()
    {
        try {
            if (!is_file($this->json_config_filename)) {
                throw new \RuntimeException( "[JS Builder] {$this->json_config_filename} not found", 2 );
            }

            if (!is_readable($this->json_config_filename)) {
                throw new \RuntimeException("[JS Builder]  {$this->json_config_filename} not readable", 3);
            }

            $json_config_content = file_get_contents( $this->json_config_filename );

            if (false === $json_config_content) {
                throw new \RuntimeException( "[JS Builer] Can't get content of {$this->json_config_filename} file." );
            }

            //$json = json_decode( $json_config_content );
            $json = json5_decode($json_config_content);

            if (null === $json) {
                throw new \RuntimeException( "[JS Builder] {$this->json_config_filename} json file is invalid", 3 );
            }

            $this->json = $json;

        } catch (\RuntimeException $e) {
            $this->error = true;
            $this->error_message = $e->getMessage();
            // $this->error_message = json_last_error_msg();
        }
    }

    private function loadConfig_MySQL()
    {

    }



}