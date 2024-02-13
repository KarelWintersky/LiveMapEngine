<?php

namespace Livemap\Controllers;

use Livemap\AbstractClass;
use Livemap\Units\Storage;

class PagesController extends AbstractClass
{
    public function __construct()
    {
        parent::__construct();
    }

    public function view_frontpage()
    {
        $storage = new Storage();

        $this->template->assign("inner_template", "pages/frontpage.tpl");
        $this->template->assign("maps_list", $storage->getPublicMapsList());

    }

}