<?php
/**
 * User: Arris
 * Date: 21.01.2018, time: 11:25
 */
 
 
class LMEMapConfigLoader {
    const VERSION   = '1.0';

    public $ERROR = FALSE;
    public $ERROR_MESSAGE = '';

    /**
     * Алиас карты
     * @var string
     */
    private $map_alias = '';

    /**
     * Режим конфигурации
     * @var string
     */
    private $config_type = '';

    /**
     * Имя конфиг-файла
     * @var string
     */
    private $json_config_filename = '';

    // конфиг
    private $CONFIG = '';

    public function __construct($map_alias, $mode = 'file', \DBConnectionLite $dbi) {

        $this->map_alias = $map_alias;
        $this->json_config_filename = $json_config_filename = PATH_STORAGE . $this->map_alias . '/index.json';

        try {
            if ($this->map_alias == NULL)
                throw new \Exception("[JS Builder] Map alias not defined", 1);

            $this->config_type = $mode;

        } catch (\Exception $e) {
            $this->ERROR = TRUE;
            $this->ERROR_MESSAGE = $e->getMessage();
        }
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

        if ($this->ERROR) die($this->ERROR_MESSAGE);

        return $this->CONFIG;
    }

    private function loadConfig_File() {
        try {
            if (!is_file($this->json_config_filename))
                throw new \Exception("[JS Builder] {$this->json_config_filename} not found", 2);

            $json_config_content = file_get_contents( $this->json_config_filename );

            if (!$json_config_content)
                throw new \Exception("[JS Builer] Can't get content of {$this->json_config_filename} file.");

            $json = json_decode( $json_config_content );

            if (!$json)
                throw new Exception("[JS Builder] {$this->json_config_filename} json file is invalid", 3);

            $this->CONFIG = $json;

        } catch (\Exception $e) {
            $this->ERROR = TRUE;
            $this->ERROR_MESSAGE = json_last_error_msg();
        }
    }

    private function loadConfig_MySQL() {

    }


}