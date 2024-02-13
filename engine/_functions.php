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
