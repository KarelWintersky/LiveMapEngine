<?php

namespace Livemap;

class TemplateHelper
{
    private static array $titles;

    public static function addTitle(string $title_part): void
    {
        self::$titles[] = $title_part;
    }

    public static function makeTitle(string $separator = " ", bool $sort = true, bool $reverse_order = false, bool $clean_extra_spaces = true):string
    {
        $t = self::$titles;

        if ($sort) {
            ksort($t);
        }

        if ($reverse_order) {
            $t = array_reverse($t);
        }

        $t = implode($separator, $t);

        if ($clean_extra_spaces) {
            $t = preg_replace('/\s+/', ' ', $t);
        }

        return $t;
    }

}