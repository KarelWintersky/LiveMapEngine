<?php

use App\App;
use Psr\Log\LoggerInterface;

function setConfig($key = '', $value = null): void
{
    \Arris\App::factory()->addConfig([ $key => $value]);
}


/**
 * Используется в шаблоне
 *
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

function logSiteUsage(LoggerInterface $logger, $is_print = false)
{
    $metrics = [
        'time.total'        =>  number_format(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 6, '.', ''),
        'memory.usage'      =>  memory_get_usage(true),
        'memory.peak'       =>  memory_get_peak_usage(true),
        'site.url'          =>  $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
        'isMobile'          =>  App::config('features.is_mobile'),
    ];

    /**
     * @var \Arris\Database\Connector $pdo
     */
    $pdo = (App::factory())->getService('pdo');

    if (!is_null($pdo)) {
        $stats = $pdo->stats();
        $metrics['mysql.queries'] = $stats->getQueryCount();
        $metrics['mysql.time'] = $stats->getTotalQueryTime();
    }

    $metrics['ipv4'] = App::config('auth.ipv4');

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
