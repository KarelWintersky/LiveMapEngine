<?php

namespace App\Units;

use App\App;
use Arris\Entity\Path;
use Arris\Exceptions\AppRouterNotFoundException;
use Symfony\Component\Yaml\Yaml;

class MapConfigFileDriver implements MapConfigDriverInterface
{
    private array  $loaded_files = [];
    private string $loaded_format = '';

    public function getDriverName(): string
    {
        return 'file';
    }

    public function load(string $map_id): array
    {
        $fn_path = Path::create(App::config('path.storage'))->join($map_id);

        $candidates = [
            'yaml' => ['path' => $fn_path->joinName('index.yaml')->toString(), 'format' => 'yaml'],
            'yml'  => ['path' => $fn_path->joinName('index.yml')->toString(),  'format' => 'yaml'],
        ];

        $raw = null;
        foreach ($candidates as $info) {
            if (is_readable($info['path'])) {
                $this->loaded_files[] = $info['path'];
                $this->loaded_format = $info['format'];
                $raw = file_get_contents($info['path']);
                break;
            }
        }

        if (false === $raw || $raw === null) {
            throw new AppRouterNotFoundException("Карта не найдена", 404, [], [
                'method'    => 'GET',
                'map'       => $map_id
            ]);
        }

        return $this->parseYaml($raw);
    }

    public function getLoadedFiles(): array
    {
        return $this->loaded_files;
    }

    public function getLoadedFormat(): string
    {
        return $this->loaded_format;
    }

    private function parseYaml(string $raw): array
    {
        try {
            $data = Yaml::parse($raw, Yaml::PARSE_EXCEPTION_ON_INVALID_TYPE);
            return is_array($data) ? $data : throw new \RuntimeException("YAML root is not an array");
        } catch (\Exception $e) {
            throw new \RuntimeException("YAML parse error: " . $e->getMessage());
        }
    }


}
