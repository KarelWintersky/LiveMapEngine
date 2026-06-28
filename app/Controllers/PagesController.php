<?php

namespace App\Controllers;

use App\AbstractClass;
use App\App;
use App\Units\OpenGraph;
use App\Units\Storage;

class PagesController extends AbstractClass
{
    public function __construct()
    {
        parent::__construct();
        $this->template->setTemplate("_pages_public.tpl");
    }

    public function view_about()
    {
        $this->template->setTemplate("_about.tpl");
        $this->template->assign("title", "О проекте");
        $this->template->assign("is_present", true);
    }

    public function view_frontpage(): void
    {
        $storage = new Storage();

        $this->template->setTemplate("_frontpage.tpl");
        $this->template->assign("maps_list", $maps_list = $storage->getPublicMapsList());

        $this->template->assign("is_logged_in", App::config('auth.is_logged_in'));
        $this->template->assign("logged_email", App::config('auth.email'));

        $this->template->assign("og", OpenGraph::makeForMap(null));

    }


}