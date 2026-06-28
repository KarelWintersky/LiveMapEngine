<?php

namespace App\Units;

use App\App;
use Arris\Entity\Path;
use Arris\Exceptions\AppRouterNotFoundException;
use ColinODell\Json5\SyntaxError;
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
            'yaml'  => ['path' => $fn_path->joinName('index.yaml')->toString(),  'format' => 'yaml'],
            'yml'   => ['path' => $fn_path->joinName('index.yml')->toString(),   'format' => 'yaml'],
            'json5' => ['path' => $fn_path->joinName('index.json5')->toString(), 'format' => 'json5'],
            'json'  => ['path' => $fn_path->joinName('index.json')->toString(),  'format' => 'json'],
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

        return match ($this->loaded_format) {
            'yaml'  => $this->parseYaml($raw),
            'json5' => $this->parseJson5($raw),
            'json'  => $this->parseJson($raw),
            default => throw new \RuntimeException("Unknown format: {$this->loaded_format}"),
        };
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

    private function parseJson5(string $raw): array
    {
        try {
            $obj = json5_decode($raw);
            return json_decode(json_encode($obj), true) ?? throw new \RuntimeException("JSON5 decode returned null");
        } catch (SyntaxError $e) {
            throw new \RuntimeException("JSON5 parse error: " . $e->getMessage());
        }
    }

    private function parseJson(string $raw): array
    {
        $data = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("JSON parse error: " . json_last_error_msg());
        }
        return $data;
    }
}
