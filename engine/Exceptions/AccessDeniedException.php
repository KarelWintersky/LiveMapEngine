<?php

namespace Livemap\Exceptions;

use Arris\AppRouter;
use Throwable;

class AccessDeniedException extends \RuntimeException
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message . '<br><br><a href="'. AppRouter::getRouter('view.form.login') .'"> На страницу логина </a> <br>', $code, $previous);
    }
}