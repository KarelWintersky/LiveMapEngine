<?php
/**
 * User: Arris
 *
 * Class MapIsAccessibleMiddleware
 * Namespace: LME\Middleware
 *
 * Date: 14.10.2018, time: 20:36
 */

namespace LME\Middleware;

use Pecee\Http\Middleware\IMiddleware;
use Pecee\Http\Request;

use Arris\Auth;
use Arris\DB;

/**
 * Проверяет, доступна ли переденная карта данному пользователю. Если нет - редиректит на главную
 *
 * Class MapIsAccessibleMiddleware
 * @package LME\Middleware
 */
class MapIsAccessibleMiddleware implements IMiddleware
{
    public function handle(Request $request): void
    {
        $route_parts = explode( '/', $request->getUrl()->getPath());
        $map_alias = $route_parts[2];

        /**
         * проверяем, доступна ли карта для пользователя на просмотр
         *
         * В конфиге карты надо указывать: visible: NOBODY, OWNER, EDITOR, MEMBER, ANYBODY
         *
         * Соответственно с этим смотреть таблицу доступа пользователей к картам
         *
         */
    }


}