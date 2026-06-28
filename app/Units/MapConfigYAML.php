<?php

namespace App\Units;

use App\App;
use Arris\Entity\Path;
use Arris\Exceptions\AppRouterNotFoundException;
use ColinODell\Json5\SyntaxError;
use Symfony\Component\Yaml\Yaml;

class MapConfigYAML
{
    private array $defaults = [
        'version'       => 3,
        'title'         => '',
        'description'   => '',
        'type'          => 'bitmap',
        'can_edit'      => [],

        'display' => [
            'zoom'                      => 0,
            'zoom_min'                  => -3,
            'zoom_max'                  => 3,
            'zoom_mode'                 => 'slider',
            'background_color'          => '#ffffff',
            'maxbounds'                 => null,
            'cursor'                    => 'pointer',
            'custom_css'                => '',
            'viewmode'                  => '',
            'viewoptions' => [
                'order'     => 'infobox>regionbox',
                'position'  => 'topright',
                'width'     => '80%',
                'height'    => '80%',
            ],
            'about'                     => '',
                'sections' => [
                    'regions'   => true,
                    'backward'  => true,
                    'title'     => true,
                    'colorbox'  => true,
                ],
            'focus_highlight_color'     => '#ff0000',
            'focus_timeout'             => 1000,
            'focus_animate_duration'    => 0.7,
            'panning_step'              => 70,
        ],

        'display_defaults' => [
            'region' => [
                'empty' => [
                    'stroke'        => 0,
                    'borderColor'   => '#ff0000',
                    'borderWidth'   => 0,
                    'borderOpacity' => 0,
                    'fill'          => 0,
                    'fillColor'     => '#ffffff',
                    'fillOpacity'   => 0,
                ],
                'empty:hover' => [
                    'stroke'        => null,
                    'borderColor'   => null,
                    'borderWidth'   => null,
                    'borderOpacity' => null,
                    'fill'          => null,
                    'fillColor'     => null,
                    'fillOpacity'   => null,
                ],
                'present' => [
                    'stroke'        => null,
                    'borderColor'   => null,
                    'borderWidth'   => null,
                    'borderOpacity' => null,
                    'fill'          => null,
                    'fillColor'     => null,
                    'fillOpacity'   => null,
                ],
                'present:hover' => [
                    'stroke'        => null,
                    'borderColor'   => null,
                    'borderWidth'   => null,
                    'borderOpacity' => null,
                    'fill'          => null,
                    'fillColor'     => null,
                    'fillOpacity'   => null,
                ],
            ],
            'poi' => [
                'any' => [
                    'iconClass'     => 'fa fa-fort-awesome',
                    'markerColor'   => 'black',
                    'iconColor'     => 'white',
                    'iconXOffset'   => -1,
                    'iconYOffset'   => 0,
                ],
                'empty' => [
                    'iconClass'     => null,
                    'markerColor'   => null,
                    'iconColor'     => null,
                    'iconXOffset'   => null,
                    'iconYOffset'   => null,
                ],
                'empty:hover' => [
                    'iconClass'     => null,
                    'markerColor'   => null,
                    'iconColor'     => null,
                    'iconXOffset'   => null,
                    'iconYOffset'   => null,
                ],
                'present' => [
                    'iconClass'     => null,
                    'markerColor'   => null,
                    'iconColor'     => null,
                    'iconXOffset'   => null,
                    'iconYOffset'   => null,
                ],
                'present:hover' => [
                    'iconClass'     => null,
                    'markerColor'   => null,
                    'iconColor'     => null,
                    'iconXOffset'   => null,
                    'iconYOffset'   => null,
                ],
            ],
        ],

        'files' => [
            'image'     => '',
            'layout'    => '',
            'og_image'  => '',
        ],

        'image' => [
            'file'      => '',
            'width'     => 0,
            'height'    => 0,
            'ox'        => 0,
            'oy'        => 0,
        ],

        'layout' => [
            'file'      => '',
            'layers'    => [],
        ],

        'layers' => [],

        'edit_templates' => [
            'templates'             => [],
            'content_css'           => '',
            'template_popup_width'  => 800,
            'template_popup_height' => 400,
        ],

        'source' => [
            'type'      => 'image',
            'file'      => '',
            'zoom'      => 0,
            'zoom_min'  => 0,
            'zoom_max'  => 0,
        ],

        'colorbox' => [
            'width'     => '900px',
            'height'    => '700px',
        ],
    ];

    private array $layer_defaults = [
        'hint'      => '',
        'zoom'      => null,
        'zoom_min'  => -100,
        'zoom_max'  => 100,
        'display_defaults' => [
            'empty' => [
                'stroke'        => 0,
                'borderColor'   => '#000000',
                'borderWidth'   => 0,
                'borderOpacity' => 0,
                'fill'          => 1,
                'fillColor'     => '#0000ff',
                'fillOpacity'   => 0.3,
            ],
            'present' => [
                'stroke'        => 0,
                'borderColor'   => '#000000',
                'borderWidth'   => 0,
                'borderOpacity' => 0,
                'fill'          => 1,
                'fillColor'     => '#00ff00',
                'fillOpacity'   => 0.3,
            ],
        ],
    ];

    private string $json_config_filename;
    private string $json_config_type;

    private string $map_id;
    private ?\stdClass $config = null;

    public bool $error = false;
    public string $error_message = '';

    private array $warnings = [];

    public function __construct($map_id, $mode = 'file')
    {
        if (empty($map_id)) {
            throw new \RuntimeException("[YAML Config] Map alias not defined", 1);
        }

        $this->map_id = $map_id;
        $this->json_config_type = $mode;
    }

    public function loadConfig(): self
    {
        match ($this->json_config_type) {
            'file'  => $this->loadFromFile(),
            'mysql' => $this->loadFromMySQL(),
            default => throw new \RuntimeException("[YAML Config] Unknown config type: {$this->json_config_type}"),
        };

        return $this;
    }

    public function getConfig(): ?\stdClass
    {
        return $this->config;
    }

    public function getWarnings(): array
    {
        return $this->warnings;
    }

    public function asArray(): array
    {
        return json_decode(json_encode($this->config), true) ?? [];
    }

    private function loadFromFile(): void
    {
        $fn_path = Path::create(App::config('path.storage'))->join($this->map_id);

        $yaml   = $fn_path->joinName('index.yaml')->toString();
        $yml    = $fn_path->joinName('index.yml')->toString();
        $json5  = $fn_path->joinName('index.json5')->toString();
        $json   = $fn_path->joinName('index.json')->toString();

        $raw = null;
        if (is_readable($yaml)) {
            $this->json_config_filename = $yaml;
            $this->json_config_type = 'yaml';
            $raw = file_get_contents($yaml);
        } elseif (is_readable($yml)) {
            $this->json_config_filename = $yml;
            $this->json_config_type = 'yaml';
            $raw = file_get_contents($yml);
        } elseif (is_readable($json5)) {
            $this->json_config_filename = $json5;
            $this->json_config_type = 'json5';
            $raw = file_get_contents($json5);
        } elseif (is_readable($json)) {
            $this->json_config_filename = $json;
            $this->json_config_type = 'json';
            $raw = file_get_contents($json);
        } else {
            throw new AppRouterNotFoundException("Карта не найдена", 404, [], [
                'method'    => 'GET',
                'map'       => $this->map_id
            ]);
        }

        if (false === $raw) {
            throw new \RuntimeException("[YAML Config] Can't read {$this->json_config_filename}");
        }

        $parsed = match ($this->json_config_type) {
            'yaml'  => $this->parseYAML($raw),
            'json5' => $this->parseJSON5($raw),
            'json'  => $this->parseJSON($raw),
            default => throw new \RuntimeException("[YAML Config] Unknown file type: {$this->json_config_type}"),
        };

        if ($parsed === null) {
            throw new \RuntimeException("[YAML Config] {$this->json_config_filename} is invalid");
        }

        $parsed = $this->normalizeLayerStructures($parsed);
        $parsed = $this->normalizeCustomCss($parsed);
        $parsed = $this->normalizeEditTemplates($parsed);

        $merged = $this->deepMerge($parsed);

        $merged = $this->applyRegionInheritance($merged);
        $merged = $this->applyPOIInheritance($merged);

        $merged = $this->applyLayerDefaults($merged);

        $this->validateRequired($merged);

        $this->config = json_decode(json_encode($merged));
    }

    private function parseYAML(string $raw): ?array
    {
        try {
            $data = Yaml::parse($raw, Yaml::PARSE_EXCEPTION_ON_INVALID_TYPE);
            return is_array($data) ? $data : null;
        } catch (\Exception $e) {
            $this->error = true;
            $this->error_message = "YAML parse error: " . $e->getMessage();
            return null;
        }
    }

    private function parseJSON5(string $raw): ?array
    {
        try {
            $obj = json5_decode($raw);
            return json_decode(json_encode($obj), true) ?? null;
        } catch (SyntaxError $e) {
            $this->error = true;
            $this->error_message = "JSON5 parse error: " . $e->getMessage();
            return null;
        }
    }

    private function parseJSON(string $raw): ?array
    {
        $data = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error = true;
            $this->error_message = "JSON parse error: " . json_last_error_msg();
            return null;
        }
        return $data;
    }

    private function normalizeLayerStructures(array $data): array
    {
        if (!empty($data['layers']) && is_array($data['layers'])) {
            foreach ($data['layers'] as $name => &$layer) {
                if (isset($layer['empty']) || isset($layer['present'])) {
                    if (!isset($layer['display_defaults'])) {
                        $layer['display_defaults'] = [];
                        $this->warnings[] = "Слой '{$name}' содержит empty/present вне display_defaults — нормализовано";
                    }
                    foreach (['empty', 'present'] as $state) {
                        if (isset($layer[$state])) {
                            $layer['display_defaults'][$state] = $layer[$state];
                            unset($layer[$state]);
                        }
                    }
                }
            }
        }
        return $data;
    }

    private function normalizeCustomCss(array $data): array
    {
        if (!empty($data['display']['custom_css'])) {
            $css = $data['display']['custom_css'];
            if (is_string($css)) {
                $data['display']['custom_css'] = [$css];
            }
        }
        return $data;
    }

    private function normalizeEditTemplates(array $data): array
    {
        foreach ($data as $key => $value) {
            $clean = trim($key, '`"\'');
            if ($clean !== $key) {
                $data[$clean] = $value;
                unset($data[$key]);
                $this->warnings[] = "Ключ '{$key}' содержит кавычки/бэктики — нормализовано в '{$clean}'";
            }
        }
        return $data;
    }

    private function mergeDefaults(array $target, array $source): array
    {
        foreach ($source as $key => $value) {
            if (!array_key_exists($key, $target)) {
                $target[$key] = $value;
            } elseif (is_array($value) && is_array($target[$key])) {
                $target[$key] = $this->mergeDefaults($target[$key], $value);
            }
        }
        return $target;
    }

    private function deepMerge(array $data): array
    {
        $merged = $this->defaults;

        foreach ($data as $key => $value) {
            if (array_key_exists($key, $merged)) {
                if (is_array($value) && is_array($merged[$key])) {
                    $merged[$key] = $this->mergeDefaults($value, $merged[$key]);
                } else {
                    $merged[$key] = $value;
                }
            }
        }

        return $merged;
    }

    private function applyRegionInheritance(array $data): array
    {
        $empty = $data['display_defaults']['region']['empty'];
        $states = ['empty:hover', 'present', 'present:hover'];
        $inherit_from = ['empty' => $empty, 'empty:hover' => $empty, 'present' => $empty];

        foreach ($states as $state) {
            $parent = match ($state) {
                'present:hover' => $data['display_defaults']['region']['present'] ?? $empty,
                default => $empty,
            };

            foreach ($empty as $field => $default) {
                $val = $data['display_defaults']['region'][$state][$field] ?? null;
                if ($val === null) {
                    $data['display_defaults']['region'][$state][$field] = $parent[$field] ?? $default;
                }
            }
        }

        return $data;
    }

    private function applyPOIInheritance(array $data): array
    {
        $any = $data['display_defaults']['poi']['any'];
        $states = ['empty', 'empty:hover', 'present', 'present:hover'];
        $inherit_from = [
            'empty'         => $any,
            'empty:hover'   => $any,
            'present'       => $any,
            'present:hover' => $any,
        ];

        foreach ($states as $state) {
            $parent = match ($state) {
                'present:hover' => $data['display_defaults']['poi']['present'] ?? $any,
                'present'       => $data['display_defaults']['poi']['present'] ?? $any,
                'empty:hover'   => $data['display_defaults']['poi']['empty'] ?? $any,
                default         => $any,
            };

            foreach ($any as $field => $default) {
                $val = $data['display_defaults']['poi'][$state][$field] ?? null;
                if ($val === null) {
                    $data['display_defaults']['poi'][$state][$field] = $parent[$field] ?? $default;
                }
            }
        }

        return $data;
    }

    private function applyLayerDefaults(array $data): array
    {
        if (!empty($data['layers']) && is_array($data['layers'])) {
            foreach ($data['layers'] as $name => &$layer) {
                $layer = $this->mergeDefaults($layer, $this->layer_defaults);

                if ($layer['zoom'] === null) {
                    $layer['zoom'] = $data['display']['zoom'] ?? 0;
                }

                if (!empty($layer['display_defaults'])) {
                    $dd = $layer['display_defaults'];
                    $this->fillLayerStateDefaults($dd, 'empty', $this->layer_defaults['display_defaults']['empty']);
                    $this->fillLayerStateDefaults($dd, 'present', $this->layer_defaults['display_defaults']['present']);

                    $present = $dd['present'] ?? $dd['empty'];
                    $this->fillLayerStateDefaults($dd, 'empty:hover', $dd['empty']);
                    $this->fillLayerStateDefaults($dd, 'present:hover', $present);
                }
            }
        }

        return $data;
    }

    private function fillLayerStateDefaults(array &$dd, string $state, array $fallback): void
    {
        $fields = ['stroke', 'borderColor', 'borderWidth', 'borderOpacity', 'fill', 'fillColor', 'fillOpacity'];
        foreach ($fields as $f) {
            $dd[$state][$f] ??= $fallback[$f] ?? 0;
        }
    }

    private function validateRequired(array $data): void
    {
        $required = ['title', 'type'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $this->warnings[] = "Отсутствует обязательное поле '{$field}'";
            }
        }

        if (empty($data['image']['file'])) {
            $this->warnings[] = "Отсутствует image.file";
        }
        if (empty($data['image']['width']) || empty($data['image']['height'])) {
            $this->warnings[] = "Не указаны размеры image (width/height)";
        }

        if (empty($data['layout']['file'])) {
            $this->warnings[] = "Отсутствует layout.file — карта без SVG-разметки";
        }
        if (empty($data['layout']['layers'])) {
            $this->warnings[] = "layout.layers пуст — слои не определены";
        }

        if (!empty($data['display']['maxbounds']) && is_array($data['display']['maxbounds'])) {
            if (count($data['display']['maxbounds']) !== 2) {
                $this->warnings[] = "display.maxbounds должен содержать 2 точки";
            }
        }
    }

    private function loadFromMySQL(): void
    {
        throw new \RuntimeException("[YAML Config] MySQL config loading not yet implemented");
    }
}
