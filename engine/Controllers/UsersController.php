<?php

namespace Livemap\Controllers;

use Livemap\AbstractClass;
use Psr\Log\LoggerInterface;

/**
 * Страницы и коллбэки пользователя
 */
class UsersController extends AbstractClass
{
    public function __construct($options = [], LoggerInterface $logger = null)
    {
        parent::__construct($options, $logger);
    }

    public function view_form_profile()
    {
        dd('show my profile');
    }



}