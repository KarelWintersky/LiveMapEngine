<?php
/**
 * User: Karel Wintersky
 * Date: 19.09.2018, time: 17:30
 */
 
use Pecee\SimpleRouter\SimpleRouter;
use Arris\Template;
use Arris\Auth;

SimpleRouter::get('/', function () {
    return (new Template('index.html', '$/templates'))->render();
});

SimpleRouter::group(['prefix' => '/auth'], function (){

    //+ Form: register
    SimpleRouter::get('/register', function (){
        $t = new Template('form.register.html', '$/templates/auth');
        $t->set('strong_password_required', Auth::get('password_min_score'));
        return $t->render();
    });

    // registration process
    SimpleRouter::post('/action:register', function (){
    });

    //+ Form/Ajax: login
    SimpleRouter::get('/login', function (){

        $t = new Template('ajax.login.html', '$/templates/auth');
        $t->set('last_login', $_COOKIE[ \Arris\Config::get('auth/cookies/last_logged_user') ] ?? '');
        return $t->render();

    });
    SimpleRouter::post('/ajax:login', function (){
        // login process
    });


    SimpleRouter::get('/profile', function (){
        $t = new Template('form.profile.html', '$/templates/auth');


    });

    // Form: logout
    SimpleRouter::get('/logout', function (){

    });
    SimpleRouter::post('/action:logout', function (){

    });

});

SimpleRouter::get('/map/{map}', function ($map_alias){
    return "Show map {$map_alias}";
});


