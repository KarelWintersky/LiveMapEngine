<?php
/**
 * User: Arris
 * Date: 24.09.2017, time: 17:13
 */

/**
 * Instant redirect по указанному URL
 * @param $url
 */
function redirect($url)
{
    if (headers_sent() === false) header('Location: '.$url);
    die();
}

/**
 * Удаляет куку
 * @param $cookie_name
 */
function unsetcookie($cookie_name)
{
    unset($_COOKIE[$cookie_name]);
    setcookie($cookie_name, null, -1, '/');
}


/**
 * Эквивалент isset( array[ key ] ) ? array[ key ] : default ;
 * at PHP 7 useless, z = a ?? b;
 * А точнее z = $array[ $key ] ?? $default;
 * @param $array    - массив, в котором ищем значение
 * @param $key      - ключ
 * @param $default  - значение по умолчанию
 */
function at($array, $key, $default)
{
    return isset($array[$key]) ? $array[$key] : $default;
}

/**
 * @param $input_array
 * @param $required_key
 * @param $allowed_values
 * @param $default_value
 * @return mixed
 */
function filter_array_for_allowed($input_array, $required_key, $allowed_values, $default_value)
{
    return
        array_key_exists($required_key, $input_array)
            ? (
                in_array($input_array[ $required_key ], $allowed_values) ? $input_array[ $required_key ] : $default_value
              )
            : $default_value;
}

function var_dd($message, $arg)
{
    var_dump($message);
    echo '<br>';
    var_dump($arg);
    echo '<hr>';
}