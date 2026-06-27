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