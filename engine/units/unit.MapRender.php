<?php

/**
 * Class MapRender
 */
class MapRender extends UnitPrototype
{
    private $template;
    private $map_alias;

    public function __construct( $map_alias )
    {
        $this->map_alias = $map_alias;
    }

    public function run( $ext = '' )
    {
        $is_correct = false;
        // проверяем второй параметр роутинга - это имя карты.
        if (!empty($this->map_alias) && is_dir(PATH_STORAGE . '/' . $this->map_alias) && is_file(PATH_STORAGE . '/' . $this->map_alias . '/index.json')) {

            $filename = PATH_STORAGE . '/' . $this->map_alias . '/index.json';
            $json = json_decode( file_get_contents( $filename ) );

            // подставляем данные в шаблон
            $this->template = new Template('viewmap.folio.html', '$/templates');
            $this->template->set('/map_alias', $this->map_alias);
            $this->template->set('/viewport/background_color', $json->viewport->background_color);
            $is_correct = true;
        } else {
            // файл конфигурации карты не найден
            $this->template = new Template('404.html', '$/templates');
            $this->template->set('error_message', "File `index.json` not found at " . PATH_STORAGE . $this->map_alias . '/ ');
            die( $this->template->render() );
        }

        return $is_correct;
    }

    public function content()
    {
        if (method_exists($this->template, 'render'))
            return $this->template->render();
    }
}
