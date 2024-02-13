<?php

namespace Livemap\Units;

use Arris\Path;
use Livemap\AbstractClass;

class Storage extends AbstractClass
{
    /**
     * Возвращает список публичных карт
     *
     * @return array
     */
    public function getPublicMapsList(): array
    {
        $maps_list = [];
        $indexfile = Path::create(getenv('PATH.STORAGE'))->joinName('list.json')->toString();

        if (is_readable($indexfile)) {
            $json = json_decode( file_get_contents( $indexfile ) );

            foreach ($json->maps as $map) {
                $alias = $map->alias;
                $title = $map->title;
                $key = str_replace('.', '~', $alias);

                $maps_list[ $key ] = [
                    'alias' =>  $alias,
                    'title' =>  $title
                ];
            }
        }
        return $maps_list;
    }

}