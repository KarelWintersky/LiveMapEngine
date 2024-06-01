<?php

use Arris\Database\DBWrapper;
use Arris\Helpers\Server;
use Livemap\App;
use Psr\Log\LoggerInterface;

/**
 * @param string|array $key
 * @param $value [optional]
 * @return string|array|bool|mixed|null
 */
function config($key = '', $value = null) {
    $app = App::factory();

    if (!is_null($value) && !empty($key)) {
        $app->setConfig($key, $value);
        return true;
    }

    if (is_array($key)) {
        foreach ($key as $k => $v) {
            $app->setConfig($k, $v);
        }
        return true;
    }

    if (empty($key)) {
        return $app->getConfig();
    }

    return $app->getConfig($key);
}

function logSiteUsage(LoggerInterface $logger, $is_print = false)
{
    $metrics = [
        'time.total'        =>  number_format(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 6, '.', ''),
        'memory.usage'      =>  memory_get_usage(true),
        'memory.peak'       =>  memory_get_peak_usage(true),
        'site.url'          =>  $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
        'isMobile'          =>  config('features.is_mobile'),
    ];

    /**
     * @var DBWrapper $pdo
     */
    $pdo = (App::factory())->getService('pdo');

    if (!is_null($pdo)) {
        $stats = $pdo->getStats();
        $metrics['mysql.queries'] = $stats['total_queries'];
        $metrics['mysql.time'] = $stats['total_time'];
    }

    $metrics['ipv4'] = Server::getIP();

    /*if ($is_print) {
        $site_usage_stats = sprintf(
            '<!-- Consumed memory: %u bytes, SQL query count: %u, SQL time %g sec, Total time: %g sec. -->',
            $metrics['memory.usage'],
            $metrics['MySQL']['Queries'],
            $metrics['MySQL']['Time'],
            $metrics['time.total']
        );
        echo $site_usage_stats . PHP_EOL;
    }*/

    $logger->notice('', $metrics);
}

function filter_array_for_allowed($input_array, $required_key, $allowed_values, $default_value)
{
    return
        array_key_exists($required_key, $input_array)
            ? (
        in_array($input_array[ $required_key ], $allowed_values) ? $input_array[ $required_key ] : $default_value
        )
            : $default_value;
}

/**
 * @param $datetime
 * @return string
 *
 */
function convertDateTime($datetime):string
{
    $yearSuffux = 'г. ';
    $ruMonths = array(
        1 => 'января', 2 => 'февраля',
        3 => 'марта', 4 => 'апреля', 5 => 'мая',
        6 => 'июня', 7 => 'июля', 8 => 'августа',
        9 => 'сентября', 10 => 'октября', 11 => 'ноября',
        12 => 'декабря'
    );

    if ($datetime === "0000-00-00 00:00:00" || $datetime === "0000-00-00" || empty($datetime)) {
        return "-";
    }

    if (intval($datetime)) {
        $datetime = date('Y-m-d H:i:s', $datetime);
    }

    $year_suffix = $yearSuffux;
    list($y, $m, $d, $h, $i, $s) = sscanf($datetime, "%d-%d-%d %d:%d:%d");

    return sprintf("%s %s %s %02d:%02d", $d, $ruMonths[$m], $y ? "{$y} {$year_suffix}" : "", $h, $i);
}

/**
 * https://gist.github.com/nyamsprod/10adbef7926dbc449e01eaa58ead5feb
 *
 * @param $object
 * @param string $path
 * @param string $separator
 * @return bool
 */
function property_exists_recursive($object, string $path, string $separator = '->'): bool
{
    if (!\is_object($object)) {
        return false;
    }

    $properties = \explode($separator, $path);
    $property = \array_shift($properties);
    if (!\property_exists($object, $property)) {
        return false;
    }

    try {
        $object = $object->$property;
    } catch (Throwable $e) {
        return false;
    }

    if (empty($properties)) {
        return true;
    }

    return \property_exists_recursive($object, \implode('->', $properties));
}