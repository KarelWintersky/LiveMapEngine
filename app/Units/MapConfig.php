<?php

namespace App\Units;

class MapConfig
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

    private string $map_id;
    private MapConfigDriverInterface $driver;
    private ?\stdClass $config = null;
    private array $warnings = [];

    public bool $error = false;
    public string $error_message = '';

    public function __construct(string $map_id, ?MapConfigDriverInterface $driver = null)
    {
        if (empty($map_id)) {
            throw new \RuntimeException("[MapConfig] Map alias not defined", 1);
        }

        $this->map_id = $map_id;
        $this->driver = $driver ?? new MapConfigFileDriver();
    }

    public function loadConfig(): self
    {
        $raw = $this->driver->load($this->map_id);

        $raw = $this->normalizeLayerStructures($raw);
        $raw = $this->normalizeCustomCss($raw);
        $raw = $this->normalizeEditTemplates($raw);

        $merged = $this->deepMerge($raw);
        $merged = $this->applyRegionInheritance($merged);
        $merged = $this->applyPOIInheritance($merged);
        $merged = $this->applyLayerDefaults($merged);

        $this->validateRequired($merged);

        $this->config = json_decode(json_encode($merged));

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

    public function getDriver(): MapConfigDriverInterface
    {
        return $this->driver;
    }

    // -----------------------------------------------------------------------
    //  Normalizers
    // -----------------------------------------------------------------------

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

    // -----------------------------------------------------------------------
    //  Merge & inheritance
    // -----------------------------------------------------------------------

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

        foreach (['empty:hover', 'present', 'present:hover'] as $state) {
            $parent = match ($state) {
                'present:hover' => $data['display_defaults']['region']['present'] ?? $empty,
                default         => $empty,
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
        $inherit_map = [
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

    // -----------------------------------------------------------------------
    //  Validation
    // -----------------------------------------------------------------------

    private function validateRequired(array $data): void
    {
        foreach (['title', 'type'] as $field) {
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
}
