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
