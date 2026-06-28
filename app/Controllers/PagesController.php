<?php

namespace App\Controllers;

use App\AbstractClass;
use App\App;
use App\Units\OpenGraph;
use App\Units\Storage;
use ColinODell\Json5\SyntaxError;

class PagesController extends AbstractClass
{
    public function __construct()
    {
        parent::__construct();
        $this->template->setTemplate("_pages_public.tpl");
    }

    /**
     * Главная страница сайта
     *
     * @return void
     * @throws SyntaxError
     */
    public function view_about()
    {
        $this->template->setTemplate("_about.tpl");
        $this->template->assign("title", "О проекте");
        $this->template->assign("is_present", true);
        $this->template->assign("content", "
            <h1>StoryMaps</h1>
            <p><strong>StoryMaps</strong> — это движок для создания и публикации интерактивных карт вымышленных миров. Карты создаются для настольных ролевых игр, литературных проектов и образовательных материалов.</p>
            <p>Каждая карта содержит подробные регионы с описаниями, изображениями и ссылками. Поддерживаются растровые карты, векторные слои, тайловые наборы и WMS-карты.</p>
            <h2>Возможности</h2>
            <ul>
                <li>Интерактивные карты на Leaflet.js с произвольной привязкой координат</li>
                <li>Регионы с подробным описанием, изображениями и ссылками</li>
                <li>Несколько режимов отображения: fullscreen, folio, iframe</li>
                <li>Управление регионами через веб-интерфейс</li>
                <li>Контроль доступа и версионирование изменений</li>
                <li>Поддержка растровых, векторных, тайловых и WMS-карт</li>
            </ul>
            <p>Автор: <a href=\"https://github.com/karelwintersky\">Karel Wintersky</a></p>
            <p>Исходный код доступен на <a href=\"https://github.com/karelwintersky\">GitHub</a>.</p>
        ");
    }

    public function view_frontpage()
    {
        $storage = new Storage();

        $this->template->setTemplate("_frontpage.tpl");
        $this->template->assign("maps_list", $storage->getPublicMapsList());

        $this->template->assign("is_logged_in", App::config('auth.is_logged_in'));
        $this->template->assign("logged_email", App::config('auth.email'));

        $this->template->assign("og", OpenGraph::makeForMap(null));

    }


}