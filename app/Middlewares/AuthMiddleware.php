<?php

namespace App\Middlewares;

use App\AbstractClass;
use App\App;
use App\AuthRoles;
use App\Exceptions\AccessDeniedException;

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
            \Arris\Helpers\Server::redirect('/');
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
        if (!App::$auth->isLoggedIn() && App::$auth->hasRole(AuthRoles::ADMIN)) {
            throw new AccessDeniedException("У вас недостаточный уровень допуска. <br><br>Возможно, истекла сессия авторизации.");
        }
    }

    public function check_can_edit()
    {
        $map_alias = $_REQUEST['edit:alias:map'] ?? $_REQUEST['map'] ?? null;
        $allowed = true;

        if ($map_alias) {
            $map = (new \App\Units\MapConfigYAML($map_alias))->loadConfig()->getConfig();
            $admin_emails = getenv('AUTH.ADMIN_EMAILS') ? explode(' ', getenv('AUTH.ADMIN_EMAILS')) : [];
            $allowed_editors = array_merge($map->can_edit ?? [], $admin_emails);

            $allowed = !is_null(App::config('auth.email')) && in_array(App::config('auth.email'), $allowed_editors);
        }

        if (!$allowed) {
            throw new AccessDeniedException("У вас недостаточный уровень допуска. <br><br>Возможно, истекла сессия авторизации.");
        }
    }



}