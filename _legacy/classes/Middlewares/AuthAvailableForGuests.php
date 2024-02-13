<?php
/**
 * User: Arris
 *
 * Class AvailableForAnyUser
 * Namespace: LME\Middleware
 *
 * Date: 15.10.2018, time: 13:05
 */

namespace Middlewares;

use Livemap\Auth;
use Pecee\Http\Middleware\IMiddleware;
use Pecee\Http\Request;

class
AuthAvailableForGuests implements IMiddleware
{
    public function handle(Request $request): void
    {
        $user = Auth::getCurrentUser();

        if ($user !== false) {
            redirect( url('page.frontpage')->getPath());
        }
    }

}