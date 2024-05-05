<?php

namespace Livemap\Units;

use Arris\Path;
use ColinODell\Json5\SyntaxError;
use Livemap\AbstractClass;

class Storage extends AbstractClass
{
    /**
     * Возвращает список публичных карт
     *
     * @return array
     * @throws SyntaxError
     */
    public function getPublicMapsList(): array
    {
        $maps_list = [];

        $indexfile = Path::create(getenv('PATH.STORAGE'))->joinName('list.json')->toString();
        if (!is_readable($indexfile)) {
            $indexfile = Path::create(getenv('PATH.STORAGE'))->joinName('list.json5')->toString();
        }

        if (!is_readable($indexfile)) {
            throw new \RuntimeException("Index file not readable");
        }

        $raw = file_get_contents( $indexfile );

        if (false === $raw) {
            throw new \RuntimeException("Index file not readable");
        }

        // тут надо поймать и выкинуть 500-ю ошибку
        $json = json5_decode( $raw );

        if (false === $json) {
            throw new \RuntimeException("Invalid index file");
        }

        foreach ($json->maps as $map) {
            $alias = $map->alias;
            $title = $map->title;
            $key = str_replace('.', '~', $alias); // зачем?

            $maps_list[ $key ] = [
                'alias' =>  $alias,
                'title' =>  $title
            ];
        }

        return $maps_list;
    }

}