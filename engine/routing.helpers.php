<?php

use Pecee\SimpleRouter\SimpleRouter as Router;
use Pecee\Http\Url;
use Pecee\Http\Response;
use Pecee\Http\Request;

/**
 * Get url for a route by using either name/alias, class or method name.
 *
 * The name parameter supports the following values:
 * - Route name
 * - Controller/resource name (with or without method)
 * - Controller class name
 *
 * When searching for controller/resource by name, you can use this syntax "route.name@method".
 * You can also use the same syntax when searching for a specific controller-class "MyController@home".
 * If no arguments is specified, it will return the url for the current loaded route.
 *
 * @param string|null $name
 * @param string|array|null $parameters
 * @param array|null $getParams
 * @return Url
 * @throws InvalidArgumentException
 */
function _url(?string $name = null, $parameters = null, ?array $getParams = null): Url
{
    return Router::getUrl($name, $parameters, $getParams);
}

/**
 * @return Response
 */
function _response(): Response
{
    return Router::response();
}

/**
 * @return Request
 */
function _request(): Request
{
    return Router::request();
}

/**
 * Get input class
 * @param string|null $index Parameter index name
 * @param string|null $defaultValue Default return value
 * @param array ...$methods Default methods
 * @return \Pecee\Http\Input\InputHandler|array|string|null
 */
function _input($index = null, $defaultValue = null, ...$methods)
{
    if ($index !== null) {
        return _request()->getInputHandler()->value($index, $defaultValue, ...$methods);
    }

    return _request()->getInputHandler();
}

/**
 * @param string $url
 * @param int|null $code
 */
function _redirect(string $url, ?int $code = null): void
{
    if ($code !== null) {
        _response()->httpCode($code);
    }

    _response()->redirect($url);
}

/**
 * Get current csrf-token
 * @return string|null
 */
function _csrf_token(): ?string
{
    $baseVerifier = Router::router()->getCsrfVerifier();
    if ($baseVerifier !== null) {
        return $baseVerifier->getTokenProvider()->getToken();
    }

    return null;
}

