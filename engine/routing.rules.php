<?php
/**
 * User: Karel Wintersky
 * Date: 19.09.2018, time: 17:30
 */
 
use Pecee\SimpleRouter\SimpleRouter;
use Arris\Template;
use Arris\Auth;

SimpleRouter::get('/', function () {
    $auth = Auth::getInstance();
    $userinfo = $auth->getCurrentSessionUserInfo();

    $t = new Template('index.html', '$/templates');
    $t->set('authinfo', [
        'is_logged' =>  $auth->isLogged(),
        'email'     =>  $userinfo['email'] ?? '',
        'ip'        =>  $userinfo['ip'] ?? ''
    ]);

    {
        $maps_list = [];
        $indexfile = __ROOT__ . \Arris\Config::get('storage/maps') . '/list.json';

        if (is_readable($indexfile)) {
            $json = json_decode( file_get_contents( $indexfile ) );

            foreach ($json->maps as $i => $map) {
                $alias = $map->alias;
                $title = $map->title;
                $key = str_replace('.', '~', $alias);

                $maps_list[ $key ] = [
                    'alias' =>  $alias,
                    'title' =>  $title
                ];
            }
        }
    }

    $t->set('maps_list', $maps_list);

    return $t->render();
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
        $auth = Auth::getInstance();

        if ($auth->isLogged()) die('hacking attempt');

        $additional_fields = [
            'username'  =>  input('register:data:username', "Anonymous"),
            'gender'    =>  input('register:data:gender', 'N'),
            'city'      =>  input('register:data:city', '')
        ];
        /*
        $fields = [
            'email'     =>  input('register:data:email'),
            'password'  =>  input('register:data:password'),
            'repassword'=>  input('register:data:password_again')
        ];
        $auth_result = $auth->register(
            $fields['email'],
            $fields['password'],
            $fields['repassword']
        );
        */

        $auth_result = $auth->register(
            $_POST['register:data:email'],
            $_POST['register:data:password'],
            $_POST['register:data:password_again'],
            $additional_fields
        );

        if (!$auth_result['error']) {
            setcookie( \Arris\Config::get('auth/cookies/last_logged_user'), $_POST['register:data:email'], time()+60*60*5, '/' );

            $html_callback = \Arris\Config::get('auth/auto_activation') ? '/' : '/auth/activate';
        } else {
            $html_callback = '/auth/register';
        }

        redirect($html_callback, 302);

    });

    //+ Form/Ajax: login
    SimpleRouter::get('/login', function (){
        $t = new Template('ajax.login.html', '$/templates/auth');
        $t->set('last_login', $_COOKIE[ \Arris\Config::get('auth/cookies/last_logged_user') ] ?? '');
        return $t->render();

    });

    //+ Form/Ajax -> Action: log-in
    SimpleRouter::post('/ajax:login', function (){
        $t = new Template('', '', 'json');

        $auth = Auth::getInstance();
        $auth_result = $auth->login(
            $_POST["auth:data:login"],
            $_POST["auth:data:password"],
            ($_POST["auth:data:remember_me"] ?? 0)
        );

        $t->set('error', $auth_result['error']);

        if (!$auth_result['error']) {
            $cookie_name = Auth::get('cookie_name');
            setcookie( $cookie_name , $auth_result['hash'], time()+$auth_result['expire'], '/');
            Auth::unsetcookie( \Arris\Config::get('auth/cookies/new_registred_username') );

            $t->set('error_messages', "Login successful"); //@todo: Это должно быть: Dictionary::init('ru_RU'); Dictionary::get('login/successful');
        } else {
            $t->set('error_messsages', "Login error: " . $auth_result['message']);
        }

        return $t->render();
    });


    SimpleRouter::get('/profile', function () {
        $t = new Template('form.profile.html', '$/templates/auth');

        // $userinfo = Auth::getCurrentUser();
        $userinfo = Auth::getInstance()->getCurrentUser();

        $t->set('/', [
            'username'      =>  $userinfo['username'],
            'gender'        =>  $userinfo['gender'],
            'city'          =>  $userinfo['city'],
            'current_email' =>  $userinfo['email'],
            'strong_password'=> Auth::get('password_min_score'),
        ]);

        return $t->render();
    });

    //+ Form: logout
    SimpleRouter::get('/logout', function (){
        $t = new Template('ajax.logout.html', '$/templates/auth');
        $userinfo = Auth::getInstance()->getCurrentSessionUserInfo();
        $t->set('/', [
            'is_logged_user' => $userinfo['email'],
            'is_logged_user_ip' => $userinfo['ip']
        ]);
        return $t->render();

    });
    SimpleRouter::post('/action:logout', function (){
        $auth = Auth::getInstance();
        $userinfo = $auth->getCurrentSessionUserInfo();

        // Вот тут нужен middleware, проверяющий, мы вообще залогинены или нет.
        // пока что обойдемся тупой антихакерской проверкой

        $t = new Template('', '', 'json');

        if ($auth->isLogged()) {

            $session_hash = $auth->getCurrentSessionHash();
            $auth_result = $auth->logout($session_hash);

            $t->set('error', $auth_result['error']);

            if ($auth_result) {
                Auth::unsetcookie( Auth::get('cookie_name') );
                setcookie( \Arris\Config::get('auth/cookies/last_logged_user'), $userinfo['email']);
                $t->set('error_messages', 'Мы успешно вышли из системы.');
            } else {
                $t->set('error_messages', 'UNKNOWN Error while logging out!');
            }
        } else {
            $t->set('error_messages', 'We are not logged in!!!');
        }

        return $t->render();
    });

});

SimpleRouter::get('/map/{map}', function ($map_alias){
    return "Show map {$map_alias}";
});


