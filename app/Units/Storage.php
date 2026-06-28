<?php

namespace App\Units;

use App\App;
use Arris\Entity\Path;
use Symfony\Component\Yaml\Yaml;

class Storage
{
    public function getPublicMapsList(): array
    {
        $maps_list = [];

        $path_storage = App::config('path.storage');

        $map_list = $this->loadMapsList($path_storage);

        foreach ($map_list as $map) {
            $alias = $map['alias'];
            $title = $map['title'];
            $key = str_replace('.', '~', $alias);

            $description = '';
            $image_url = '';

            try {
                $cfg = (new MapConfig($alias))->loadConfig()->getConfig();

                $description = $cfg->description ?? '';

                $image_file = $cfg->files->og_image
                    ?? $cfg->files->image
                    ?? '';

                $image_url = $image_file
                    ? "/storage/{$alias}/{$image_file}"
                    : '';
            } catch (\Throwable) {
                // map config not found — skip enrichment
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

    private function loadMapsList(string $path_storage): array
    {
        $paths = [
            'yaml' => Path::create($path_storage)->joinName('list.yaml')->toString(),
            'yml'  => Path::create($path_storage)->joinName('list.yml')->toString(),
        ];

        foreach (['yaml', 'yml'] as $fmt) {
            if (is_readable($paths[$fmt])) {
                $raw = file_get_contents($paths[$fmt]);
                if (false === $raw) {
                    continue;
                }

                return $this->parseYamlList($raw);
            }
        }

        throw new \RuntimeException("Index file not readable (tried yaml/yml)");
    }

    private function parseYamlList(string $raw): array
    {
        $data = Yaml::parse($raw, Yaml::PARSE_EXCEPTION_ON_INVALID_TYPE);
        return $data['maps'] ?? throw new \RuntimeException("Invalid YAML index file: missing 'maps' key");
    }
}
