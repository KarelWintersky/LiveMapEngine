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

        $json = json5_decode( $raw );

        if (false === $json) {
            throw new \RuntimeException("Invalid index file");
        }

        foreach ($json->maps as $map) {
            $alias = $map->alias;
            $title = $map->title;
            $key = str_replace('.', '~', $alias);

            $description = '';
            $image_url = '';

            $config_path = Path::create(getenv('PATH.STORAGE'))->join($alias)->joinName('index.json5');
            if (!is_readable($config_path)) {
                $config_path = Path::create(getenv('PATH.STORAGE'))->join($alias)->joinName('index.json');
            }
            if (is_readable($config_path)) {
                $raw_config = file_get_contents($config_path);
                if (false !== $raw_config) {
                    $config = json5_decode($raw_config);
                    if ($config) {
                        $description = $config->description ?? '';
                        $image_file = $config->files->og_image
                            ?? $config->files->image
                            ?? '';
                        $image_url = $image_file
                            ? "/storage/{$alias}/{$image_file}"
                            : '';
                    }
                }
            }

            $maps_list[ $key ] = [
                'alias'       => $alias,
                'title'       => $title,
                'description' => $description,
                'image_url'   => $image_url,
            ];
        }

        return $maps_list;
    }

}