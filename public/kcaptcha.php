<?php

define('PATH_ROOT', dirname(__DIR__, 1));

use AJUR\Template\KCaptcha;

require_once PATH_ROOT . '/vendor/autoload.php';

if (isset( $_REQUEST[ 'sid' ] )) { session_id( $_REQUEST[ 'sid' ] ); }
session_start();

$captcha = new KCaptcha([
    'width' => 120,
    'height' => 60,
    'fluctuation_amplitude' => 4,
    'no_spaces' => false,
    'show_credits' => false,
    'codeSet' => '23456789abcdegikpqsvxyz',
    'length' => random_int(5, 6),
    'white_noise_density' => 1 / 8,
    'black_noise_density' => 1 / 30,
]);

$captcha->display();

$name
    = array_key_exists("type", $_REQUEST) && strlen($_REQUEST["type"]) > 0
    ? "captcha_keystring_{$_REQUEST["type"]}"
    : 'captcha_keystring';
$_SESSION[$name] = $captcha->getKeyString();

