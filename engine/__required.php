<?php
/**
 * User: Arris
 * Date: 24.09.2017, time: 14:53
 */
if (version_compare(phpversion(), '7.0', '<')) {
    die('PHP 7.0+ required for this engine');
}

// defines
define('PATH_CONFIG',   __ROOT__ . '/engine/.config/');
define('PATH_ENGINE',   __ROOT__ . '/engine/');
define('PATH_FRONTEND', __ROOT__ . '/frontend/');
define('PATH_TEMPLATES',__ROOT__ . '/templates/');
define('PATH_STORAGE',  __ROOT__ . '/storage/');

// Base libs
require_once (__ROOT__ . '/engine/core.functions.php');

// WebSun Template Engine
ini_set('pcre.backtrack_limit', 1024*1024);
require_once (__ROOT__ . '/engine/external/websun.php');

// Classes
require_once(__ROOT__ . '/engine/core.LMEConfig.php');
require_once (__ROOT__ . '/engine/classes/class.INI_Config.php');
require_once (__ROOT__ . '/engine/classes/class.DBConnectionLite.php');
require_once (__ROOT__ . '/engine/classes/class.ParseSVG.php');
require_once (__ROOT__ . '/engine/classes/class.CLIConsole.php');
require_once (__ROOT__ . '/engine/classes/class.Template.php');
require_once (__ROOT__ . '/engine/classes/proto.UnitPrototype.php');

// INIT
$main_config = new INI_Config(PATH_CONFIG . 'config.ini');
$main_config->append(PATH_CONFIG . 'db.ini');
LMEConfig::set_mainconfig( $main_config );

$dbi = new DBConnectionLite('livemap', $main_config);
LMEConfig::set_dbi( $dbi );

// PHPAuth
if ($main_config->get('auth/phpauth_enabled')) {
    require_once (__ROOT__ . '/engine/external/phpauth/config.class.php');
    require_once (__ROOT__ . '/engine/external/phpauth/auth.class.php');

    LMEConfig::set_authconfig(
        new PHPAuth\Config( PATH_CONFIG . 'phpauth.ini' )
    );
    LMEConfig::set_auth(
        new PHPAuth\Auth( LMEConfig::get_dbi()->getconnection(), LMEConfig::get_authconfig() )
    );
}

// LiveMap Engine
require_once (__ROOT__ . '/engine/core.LiveMapEngine.php');



