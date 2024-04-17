<?php

namespace Livemap;

class Common
{
    /**
     *
     * @param int $size
     * @param int $decimals
     * @param string $decimal_separator
     * @param string $thousands_separator
     * @return string
     */
    public static function size_format(int $size, int $decimals = 0, string $decimal_separator = '.', string $thousands_separator = ','): string {
        $units = ['', 'K', 'M', 'G', 'Tb', 'PB', 'EB', 'ZB', 'YB'];
        $index = min(floor((strlen(strval($size)) - 1) / 3), count($units) - 1);
        $number = number_format($size / pow(1000, $index), $decimals, $decimal_separator, $thousands_separator);
        return sprintf('%s %s', $number, $units[$index]);
    }

}