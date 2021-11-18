<?php
/**
 * User: Arris
 *
 * Class AvailableForLoggedUserMiddleware
 * Namespace: LME\Middleware
 *
 * Date: 15.10.2018, time: 12:53
 */

namespace Livemap\Middlewares;

use Livemap\Auth;
use Pecee\Http\Middleware\IMiddleware;
use Pecee\Http\Request;

class AuthAvailableForLogged implements IMiddleware
{

    public function handle(Request $request): void
    {
        $user = Auth::getCurrentUser();

        if ($user === false) {
            redirect( url('page.frontpage')->getPath());
        }
    }

}