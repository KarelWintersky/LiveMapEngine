<?php

namespace Livemap\Controllers;

use Psr\Log\LoggerInterface;

class AdminController extends \Livemap\AbstractClass
{
    public function __construct($options = [], LoggerInterface $logger = null)
    {
        parent::__construct($options, $logger);

        $this->template->setTemplate('_admin.tpl');
    }

    public function view_main_page()
    {
        $this->template->assign("inner_template", 'admin/main.tpl');
    }

}