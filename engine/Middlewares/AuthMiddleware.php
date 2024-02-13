<?php

namespace Livemap\Middlewares;

use Arris\Helpers\Server;
use Livemap\AbstractClass;
use Livemap\App;
use Livemap\Exceptions\AccessDeniedException;

class AuthMiddleware extends AbstractClass
{
    /**
     *
     *
     * @param $uri
     * @param $route_info
     * @return void
     */
    public function check_not_logged_in($uri, $route_info)
    {
        if (App::$auth->isLoggedIn()) {
            Server::redirect('/');
        }
    }

    /**
     * @param $uri
     * @param $route_info
     * @return void
     */
    public function check_is_logged_in($uri, $route_info)
    {
        if (!App::$auth->isLoggedIn()) {
            throw new AccessDeniedException("Вы не авторизованы. <br><br>Возможно, истекла сессия авторизации.");
        }
    }

    /**
     * @param $uri
     * @param $route_info
     * @return void
     */
    public function check_is_admin_logged($uri, $route_info)
    {
        if (!App::$auth->isLoggedIn() && App::$auth->hasRole(\Livemap\AuthRoles::ADMIN)) {
            throw new AccessDeniedException("У вас недостаточный уровень допуска. <br><br>Возможно, истекла сессия авторизации.");
        }

    }



}