<?php

namespace Livemap\Controllers;

use Psr\Log\LoggerInterface;

class AdminController extends \Livemap\AbstractClass
{
    public function __construct($options = [], LoggerInterface $logger = null)
    {
        parent::__construct($options, $logger);
    }

    public function view_main_page()
    {
        dd('главная страница админки');
    }

}