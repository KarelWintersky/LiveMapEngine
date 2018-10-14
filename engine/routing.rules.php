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

SimpleRouter::group([ 'where' => ['map_alias' => '[\w\d\.]+']], function (){

    SimpleRouter::get('/map/{map_alias}', 'MapView@view_map_fullscreen');

    SimpleRouter::get('/map:iframe/{map_alias}', 'MapView@view_map_iframe');

    SimpleRouter::get('/map:folio/{map_alias}', 'MapView@view_map_folio');
});


