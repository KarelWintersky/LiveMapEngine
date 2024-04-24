<?php

namespace Livemap;

class OpenGraph
{
    private static function getDefault()
    {
        return [
            'url'           =>  "{$_SERVER['REQUEST_SCHEME']}://{$_SERVER['HTTP_HOST']}/",
            'type'          =>  'website',
            'title'         =>  "STORYMAPS - Карты и истории",
            'description'   =>  "STORYMAPS - Карты и истории",
            'image'         =>  "{$_SERVER['REQUEST_SCHEME']}://{$_SERVER['HTTP_HOST']}/frontend/og_image.png",
            'logo'          =>  "{$_SERVER['REQUEST_SCHEME']}://{$_SERVER['HTTP_HOST']}/frontend/og_image.png",
            'site_name'     =>  "STORYMAPS - Карты и истории",

            'domain'        =>  $_SERVER['HTTP_HOST'],
        ];
    }

    /**
     * Генерирует OpenGraph-информацию для страницы
     *
     * @param string $map_alias
     * @param \stdClass|null $map
     * @return array
     */
    public static function getInfo(string $map_alias = '', \stdClass $map = null)
    {
        $OG_DEFAULT = self::getDefault();

        if (empty($map_alias) || is_null($map)) {
            return $OG_DEFAULT;
        }

        $OG = [
            'url'           =>  "{$_SERVER['REQUEST_SCHEME']}://{$_SERVER['REQUEST_URI']}/",
            'type'          =>  'website',
            'domain'        =>  $_SERVER['HTTP_HOST']
        ];
        $OG['title'] = $OG['site_name']
            = !empty($map->title)
            ? "STORYMAPS - " . $map->title
            : $OG_DEFAULT['title'];

        $OG['description']
            = "STORYMAPS - " . ($map->description ?: $map->title);

        $OG['image']
            = $OG['logo']
            = !empty($map->files->image)
            ? "{$_SERVER['REQUEST_SCHEME']}://{$_SERVER['HTTP_HOST']}/storage/{$map_alias}/{$map->files->image}"
            : $OG_DEFAULT['image'];

        return $OG;
    }

}