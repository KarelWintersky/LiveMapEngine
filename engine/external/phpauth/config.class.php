<?php
namespace PHPAuth;

/**
 * PHPAuth Config class
 */
class Config
{
    private $config;

    /**
     * Конструктор
     * @param string $config_file
     */
    public function __construct($config_file = 'phpauth.ini')
    {
        $this->config = array();

        $configfile = ($config_file == '') ? 'phpauth.ini' : $config_file;
        $path = $configfile;

        if (file_exists($path)) {
            $parsed = parse_ini_file($path);
            foreach ($parsed as $setting => $value) {
                $this->config [$setting] = $value;
            }
        } else {
            die("<strong>FATAL ERROR:</strong> PHPAuth config file `{$path}` not found or not exists. ");
        }

        $this->setForgottenDefaults(); // Danger foreseen is half avoided.
    }

    /**
     * Config::__get()
     * 
     * @param mixed $setting
     * @return string
     */
    public function __get($setting)
    {
        return $this->config[$setting];
    }

    /**
     * Config::__set()
     *
     * @param mixed $setting
     * @param mixed $value
     * @return bool
     */
    public function __set($setting, $value)
    {
        return false;
    }

    /**
     * Config::override()
     * 
     * @param mixed $setting
     * @param mixed $value
     * @return bool
     */
    public function override($setting, $value){

        $this->config[$setting] = $value;
        return true;
    }

    /** @todo:
     * Deprecated ?
     * @param $name
     * @param $args
     */
    public function __call($name, $args)
    {
        if ($name == 'get') {
            return $this->config[$args[0]];
        } elseif ($name == 'getAll') {
            return $this->config;
        } else {
            var_dump('Called undefined method: ' . $name);
            return false;
        }
    }


    /**
     * Danger foreseen is half avoided. Verify values.
     *
     * Set default values.
     * REQUIRED FOR USERS THAT DOES NOT UPDATE THEIR `config` TABLES or config file.
     */
    private function setForgottenDefaults()
    {
        if (! isset($this->config['verify_password_min_length']) )
            $this->config['verify_password_min_length'] = 3;

        if (! isset($this->config['verify_password_max_length']) )
            $this->config['verify_password_max_length'] = 150;

        if (! isset($this->config['verify_password_strong_requirements']) )
            $this->config['verify_password_strong_requirements'] = 1;

        if (! isset($this->config['verify_email_min_length']) )
            $this->config['verify_email_min_length'] = 5;

        if (! isset($this->config['verify_email_max_length']) )
            $this->config['verify_email_max_length'] = 100;

        if (! isset($this->config['verify_email_use_banlist']) )
            $this->config['verify_email_use_banlist'] = 1;

        if (! isset($this->config['emailmessage_suppress_activation']) )
            $this->config['emailmessage_suppress_activation'] = 0;

        if (! isset($this->config['emailmessage_suppress_reset']) )
            $this->config['emailmessage_suppress_reset'] = 0;

    }


}
