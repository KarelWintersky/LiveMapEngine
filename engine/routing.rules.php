<?php
/**
 * User: Karel Wintersky
 * Date: 19.09.2018, time: 17:30
 */
 
use Pecee\SimpleRouter\SimpleRouter;
use Arris\Template;
use Arris\Auth;

SimpleRouter::setDefaultNamespace('LME\Units');

SimpleRouter::get('/', 'Pages@view_page_frontpage');

SimpleRouter::group(['prefix' => '/auth'], function (){

    SimpleRouter::get('/register', 'User@view_page_register');
    SimpleRouter::post('/action:register', 'User@action_register');

    SimpleRouter::get('/login', 'User@view_ajax_login');
    SimpleRouter::post('/ajax:login', 'User@action_login');

    SimpleRouter::get('/logout', 'User@view_ajax_logout');
    SimpleRouter::post('/action:logout', 'User@action_logout');

    SimpleRouter::get('/profile', 'User@view_page_profile');

});

SimpleRouter::get('/map/{map}', function ($map_alias){
    return "Show map {$map_alias}";
}, ['where' => ['map' => '[\w\d\.]+']]);


