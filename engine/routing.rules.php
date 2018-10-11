<?php
/**
 * User: Karel Wintersky
 * Date: 19.09.2018, time: 17:30
 */
 
use Pecee\SimpleRouter\SimpleRouter;
use Arris\Template;

SimpleRouter::get('/', function () {
    return (new Template('index.html', '$/templates'))->render();
});

SimpleRouter::group(['prefix' => '/auth'], function (){

    SimpleRouter::get('/register', function (){
        return (new Template('form.register.html', '$/templates/auth'))->render();
    });
    SimpleRouter::post('/action:register', function (){
        // registration process
    });

    // аякс-форма логина
    SimpleRouter::get('/login', function (){
        return (new Template('ajax.login.html', '$/templates/auth'))->render();
    });
    SimpleRouter::post('/ajax:login', function (){
        // login process
    });
    SimpleRouter::get('/profile', function (){
        $t = new Template('form.profile.html', '$/templates/auth');


    });
    SimpleRouter::get('/logout', function (){

    });
});

SimpleRouter::get('/map/{map}', function ($map_alias){
    return "Show map {$map_alias}";
});


