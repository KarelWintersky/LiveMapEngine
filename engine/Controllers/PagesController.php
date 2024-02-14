<?php

namespace Livemap\Controllers;

use Livemap\AbstractClass;
use Livemap\Units\Storage;

class PagesController extends AbstractClass
{
    public function __construct()
    {
        parent::__construct();
        $this->template->setTemplate("_pages(legacy).tpl");
        // $this->template->setTemplate("_pages(flex).tpl");
    }

    public function view_frontpage()
    {
        $storage = new Storage();

        $this->template->assign("inner_template", "pages/frontpage.tpl");
        $this->template->assign("maps_list", $storage->getPublicMapsList());

        $this->template->assign("is_logged_in", config('auth.is_logged_in'));
        $this->template->assign("logged_email", config('auth.email'));

    }

}