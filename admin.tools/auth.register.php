<?php

use App\App;
use Arris\Database\Config;
use Arris\Database\Connector;
use Arris\DelightAuth\Auth\Auth;

date_default_timezone_set('Europe/Moscow');

define("__ROOT__", dirname(__DIR__));
define('ENGINE_START_TIME', microtime(true));

const APP_CONFIG = '/etc/arris/livemap/config.yaml';

require_once __DIR__ . '/../vendor/autoload.php';

try {
    App::init([ APP_CONFIG ]);

    $pdo_config = new Config();
    $pdo_config
        ->setUsername(App::config('database.username'))
        ->setPassword(App::config('database.password'))
        ->setDatabase(App::config('database.database'));

    $pdo = new Connector($pdo_config);
    $auth = new Auth($pdo);

    // get users
    $users = $pdo->query("SELECT id, email, username  FROM auth_users ORDER BY id")->fetchAll();


    Laravel\Prompts\info('Users: ');
    Laravel\Prompts\table(
        headers: ['id', 'E-Mail', 'Username'],
        rows: $users
    );

    $email = Laravel\Prompts\text(
        label: 'Введите email пользователя:',
        required: true,
        validate: function (string $email) {
            return filter_var($email, FILTER_VALIDATE_EMAIL) ? null : 'Incorrect email';
        }
    );

    $username = Laravel\Prompts\text(
        label: 'Введите имя пользователя: ',
        required: true,
        validate: fn (string $value) => match (true) {
            strlen($value) < 3 => 'The name must be at least 3 characters.',
            strlen($value) > 255 => 'The name must not exceed 255 characters.',
            default => null
        }
    );

    $password = Laravel\Prompts\password(
        label: 'Введите пароль пользователя: ',
        required: true,
        validate: fn (string $value) => match (true) {
            strlen($value) < 8 => 'The password must be at least 8 characters.',
            default => null
        },
        hint: 'Minimum 8 characters.'
    );

    $userId = $auth->admin()->createUser($email, $password, $username);

    var_dump($userId);

    $auth->admin()->addRoleForUserById($userId, AuthRoles::ADMIN);

    echo 'We have created and activated a new user with the ID ' . $userId . PHP_EOL;



}catch (InvalidEmailException $e) {
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

