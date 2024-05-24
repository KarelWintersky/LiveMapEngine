<?php

namespace Livemap\Controllers;

use Livemap\AbstractClass;
use Livemap\OpenGraph;
use Livemap\Units\Storage;

/**
 * Контроллер отвечает за главную страницу и прочие информационные
 */
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
     */
    public function view_frontpage()
    {
        $storage = new Storage();

        $this->template->assign("inner_template", "pages/frontpage.tpl");
        $this->template->assign("maps_list", $storage->getPublicMapsList());

        $this->template->assign("is_logged_in", config('auth.is_logged_in'));
        $this->template->assign("logged_email", config('auth.email'));

        $this->template->assign("og", OpenGraph::makeForMap(null));

    }

}