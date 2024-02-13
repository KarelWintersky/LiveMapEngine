<?php

use Arris\DelightAuth\Auth\Auth;
use Arris\DelightAuth\Auth\InvalidEmailException;
use Arris\DelightAuth\Auth\InvalidPasswordException;
use Arris\DelightAuth\Auth\TooManyRequestsException;
use Arris\DelightAuth\Auth\UserAlreadyExistsException;
use Dotenv\Dotenv;

define("__ROOT__", dirname(__DIR__));
define('ENGINE_START_TIME', microtime(true));
define('PATH_CONFIG', '/etc/arris/livemap/');

$PATH_INSTALL = dirname(__DIR__, 1);

require_once __ROOT__ . '/vendor/autoload.php';

foreach ([ 'site.conf' ] as $file) { Dotenv::create( PATH_CONFIG, $file )->load(); }

$connection = [
    'driver'    =>  'mysql',
    'hostname'  =>  'localhost',
    'username'  =>  getenv('DB.USERNAME'),
    'password'  =>  getenv('DB.PASSWORD'),
    'database'  =>  getenv('DB.NAME'),
    'charset'   =>  'utf8mb4',
];

$db = new \PDO(
    "mysql:dbname={$connection['database']};host={$connection['hostname']};charset=utf8mb4",
    $connection['username'],
    $connection['password']
);

$auth = new Auth($db);

$cli_options = getopt('', ['help', 'email:', 'password:', 'role:', 'username:']);

$credentials = [
    'email'     =>  array_key_exists('email', $cli_options) ? $cli_options['email'] : '',
    'password'  =>  array_key_exists('password', $cli_options) ? $cli_options['password'] : '',
    'is_admin'  => array_key_exists('role', $cli_options) && $cli_options['role'] == 'admin'
];

if (empty($credentials['email']) || empty($credentials['password'])) {
    echo <<<MSG1
Both EMAIL and PASSWORD options required. 
Use register.php --email email --password password [--is_admin] 
MSG1;
    echo PHP_EOL;
    die;
}

$credentials['username'] = array_key_exists('username', $cli_options) ? $cli_options['username'] : explode('@', $credentials['email'])[0];

try {
    $userId = $auth->admin()->createUser($credentials['email'], $credentials['password'], $credentials['username']);

    if ($credentials['is_admin']) {
        $auth->admin()->addRoleForUserById($userId, \Arris\DelightAuth\Auth\Role::ADMIN);
    } else {
        $auth->admin()->addRoleForUserById($userId, \Arris\DelightAuth\Auth\Role::EDITOR);
    }

    echo 'We have created and activated a new user with the ID ' . $userId . PHP_EOL;
}
catch (InvalidEmailException $e) {
    die('Invalid email address');
}
catch (InvalidPasswordException $e) {
    die('Invalid password');
}
catch (UserAlreadyExistsException $e) {
    die('User already exists');
}
catch (TooManyRequestsException $e) {
    die('Too many requests');
}




