<?php

namespace App\Units;

use App\App;
use Arris\Entity\Path;
use ColinODell\Json5\SyntaxError;
use Symfony\Component\Yaml\Yaml;

class Storage
{
    /**
     * @throws SyntaxError
     */
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
                $cfg = (new MapConfigYAML($alias))->loadConfig()->getConfig();

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
            'yaml'  => Path::create($path_storage)->joinName('list.yaml')->toString(),
            'yml'   => Path::create($path_storage)->joinName('list.yml')->toString(),
            'json5' => Path::create($path_storage)->joinName('list.json5')->toString(),
            'json'  => Path::create($path_storage)->joinName('list.json')->toString(),
        ];

        foreach (['yaml', 'yml', 'json5', 'json'] as $fmt) {
            if (is_readable($paths[$fmt])) {
                $raw = file_get_contents($paths[$fmt]);
                if (false === $raw) {
                    continue;
                }

                return match ($fmt) {
                    'yaml', 'yml' => $this->parseYamlList($raw),
                    'json5'       => $this->parseJsonList($raw, true),
                    'json'        => $this->parseJsonList($raw, false),
                };
            }
        }

        throw new \RuntimeException("Index file not readable (tried yaml/yml/json5/json)");
    }

    private function parseYamlList(string $raw): array
    {
        $data = Yaml::parse($raw, Yaml::PARSE_EXCEPTION_ON_INVALID_TYPE);
        return $data['maps'] ?? throw new \RuntimeException("Invalid YAML index file: missing 'maps' key");
    }

    private function parseJsonList(string $raw, bool $is_json5): array
    {
        $data = $is_json5 ? json5_decode($raw) : json_decode($raw);
        if (empty($data->maps)) {
            throw new \RuntimeException("Invalid index file");
        }

        return array_map(static fn($m) => [
            'alias' => $m->alias,
            'title' => $m->title,
        ], $data->maps);
    }
}
