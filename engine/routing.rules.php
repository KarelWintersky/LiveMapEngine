<?php
/**
 * User: Karel Wintersky
 * Date: 19.09.2018, time: 17:30
 */
 
use Pecee\SimpleRouter\SimpleRouter;
use Arris\Template;
use Arris\Auth;

SimpleRouter::setDefaultNamespace('LME\Units');

SimpleRouter::get('/', 'Pages@view_page_frontpage')->name('page.frontpage');

/*SimpleRouter::group(['prefix' => '/auth'], function (){

    SimpleRouter::get('/register', 'User@view_page_register');
    SimpleRouter::post('/action:register', 'User@action_register');

    SimpleRouter::get('/login', 'User@view_ajax_login');
    SimpleRouter::post('/ajax:login', 'User@action_login');

    SimpleRouter::get('/logout', 'User@view_ajax_logout');
    SimpleRouter::post('/action:logout', 'User@action_logout');

    SimpleRouter::get('/profile', 'User@view_page_profile');

});*/

SimpleRouter::group(['middleware' => \LME\Middleware\AuthAvailableForGuests::class], function (){

    SimpleRouter::get('/auth/register', 'User@view_page_register');
    SimpleRouter::post('/auth/action:register', 'User@action_register');

    SimpleRouter::get('/auth/login', 'User@view_ajax_login');
    SimpleRouter::post('/auth/ajax:login', 'User@action_login');

});

SimpleRouter::group(['middleware' => \LME\Middleware\AuthAvailableForLogged::class], function (){
    SimpleRouter::get('/auth/logout', 'User@view_ajax_logout');
    SimpleRouter::post('/auth/action:logout', 'User@action_logout');

    SimpleRouter::get('/auth/profile', 'User@view_page_profile');

    SimpleRouter::get('/edit/region/{map_alias}/{region_id}', 'Pages@view_page_edit_region');

    // сохранить информацию по региону
});

SimpleRouter::group([
    'where'     =>  ['map_alias' => '[\w\d\.]+'],
    'middleware'=>  \LME\Middleware\MapIsAccessibleMiddleware::class
], function (){
    SimpleRouter::get('/map/{map_alias}', 'MapView@view_map_fullscreen');

    SimpleRouter::get('/map:iframe/{map_alias}', 'MapView@view_map_iframe');

    SimpleRouter::get('/map:folio/{map_alias}', 'MapView@view_map_folio');

    // получить информацию по региону

    // получить JS-файл описания разметки
});

