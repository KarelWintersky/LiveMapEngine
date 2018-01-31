<?php
/**
 * User: Arris
 * Date: 24.09.2017, time: 18:18
 */
define('__ROOT__', __DIR__);

require_once (__ROOT__ . '/engine/__required.php');

$auth = LMEAuth::get();

$is_logged_in = LMEAuth::$is_logged;

$template_file = '';
$template_data = array();

switch ($_GET['action']) {
    case 'loginform': {
        $template_file = 'auth/form.auth.ajax.html';

        if ($is_logged_in) {
            $userinfo = LMEAuth::$userinfo;
            $template_data = array_merge($template_data, array(
                'is_logged'             =>  $is_logged_in,
                'is_logged_user'        =>  $userinfo['email'],
                'is_logged_user_ip'     =>  $userinfo['ip']
            ));
        }

        break;
    }
    case 'ajax:login': {
        $auth_result = $auth->login(
            $_POST["auth:data:login"],
            $_POST["auth:data:password"],
            ($_POST["auth:data:remember_me"] ?? 0)
            //at($_POST, "auth:data:remember_me", 0)
        );

        $template_data['error'] = $auth_result['error'];

        if (!$auth_result['error']) {
            setcookie(LMEAuth::get_config()->__get('cookie_name'), $auth_result['hash'], time()+$auth_result['expire'], "/");
            unsetcookie(LMEConfig::get_mainconfig()->get('cookies/new_registred_username'));
            $html_callback = '/';
        } else {
            $html_callback = '/auth/login';
        }

        if (!$auth_result['error']) {
            // no errors
            $html_callback = '/';
            $template_data['error_messages'] = "Login successful";
        } else {
            $template_data['error_messages'] = "Login error: " . $auth_result['message'];
            $html_callback = '/login';
        }

        $template_file = '*json';
        break;
    }
    case 'ajax:logout': {
        if ($auth->isLogged()) {

            $session_hash = $auth->getSessionHash();

            $auth_result = $auth->logout($session_hash);

            if ($auth_result) {
                unsetcookie( LMEAuth::get_config()->__get('cookie_name'));

                setcookie( LMEConfig::get_config()->get('cookies/last_logged_user'), LMEAuth::$userinfo['email']);

                $template_data['error_messages'] = 'Мы успешно вышли из системы.';
                $html_callback = '/';
            } else {
                $template_data['error_messages'] = 'UNKNOWN Error while logging out!';
            }
        } else {
            // we are not logged!
            $template_data['error_messages'] = 'We are not logged in!!!';
        }
        redirect('/');
        $template_file = '*json';
        break;
    }


    //+ форма входа
    case 'login': {

        if ($is_logged_in) {
            $template_file = 'auth.callback.instant_to_root.html';
            redirect('/');
        } else {
            $template_data['new_username'] = $_COOKIE[ LMEConfig::get_mainconfig()->get('cookies/last_logged_user') ] ?? '';
            $template_data['autoactivation'] = ! LMEConfig::get_mainconfig()->get('auth/auto_activation');
            $template_file = 'auth/form.login.html';
        }
        break;
    }

    // форма регистрации
    case 'register': {
        if ($is_logged_in) {
            $template_file = 'auth.callback.instant_to_root.html';
            redirect('/');
        } else {
            $template_data['strong_password'] = LMEConfig::get_authconfig()->verify_password_strong_requirements;
            $template_file = 'auth/form.register.html';
        }
        break;
    }

    // форма восстановления пароля
    case 'recover': {
        // если мы залогинились - глупо пытаться восстановить пароль
        if ($is_logged_in) {
            $template_file = 'auth.callback.instant_to_root.html';
            redirect('/');
        } else {
            $template_file = 'auth/form.recover.html';
        }
        break;
    }

    //+ profile
    case 'profile': {
        if ($is_logged_in) {
            $userinfo = LMEAuth::$userinfo;

            $template_data = array(
                'username'      =>  $userinfo['username'],
                'gender'        =>  $userinfo['gender'],
                'city'          =>  $userinfo['city'],
                'current_email' =>  $userinfo['email'],
                'strong_password'=> LMEAuth::get_config()->__get('verify_password_strong_requirements'),
            );


            $template_file = 'auth/form.profile.html';
        } else {
            $template_file = 'auth.callback.instant_to_root.html';
            redirect('/');
        }
        break;
    }

    //+
    case 'logout': {
        if ($is_logged_in) {
            $template_file = 'auth/form.logout.html';
        } else {
            $template_file = 'auth.callback.instant_to_root.html';
            redirect('/');
        }
        break;
    }

    // actions

    // ввод ключа активации аккаунта
    case 'activateaccount': {
        if ($is_logged_in) {
            // Активация аккаунта недоступна если мы залогинились
            $template_file = 'auth.callback.instant_to_root.html';
            redirect('/');
        } else {
            $template_file = 'auth/form.activate.html';
        }
        break;
    }

    // ввод ключа сброса пароля
    case 'resetpassword': {
        if ($is_logged_in) {
            $template_file = 'auth.callback.instant_to_root.html';
            redirect('/');
        } else {
            $template_file = 'auth/form.resetpassword.html';
        }
        break;
    }

    /* ============== actions ============= */

    //+
    case 'action:login': {
        $html_callback = '/';
        if (!$is_logged_in && $_POST['login:data:action'] === 'login') {
            $auth_result = $auth->login(
                $_POST["login:data:name"],
                $_POST["login:data:password"],
                ($_POST['login:data:remember_me'] ?? 0)
            );

            if (!$auth_result['error']) {
                setcookie(LMEAuth::get_config()->__get('cookie_name'), $auth_result['hash'], time()+$auth_result['expire'], "/");

                // setcookie(LMEConfig::get_authconfig()->__get('cookie_name'), $auth_result['hash'], time()+$auth_result['expire'], "/");

                unsetcookie( LMEConfig::get_config()->get('cookies/new_registred_username') );

                $html_callback = '/';
            } else {
                $html_callback = '/auth/login';
            }
        }

        redirect($html_callback);

        break;
    }

    //+
    case 'action:logout': {
        if ($is_logged_in) {
            $userinfo = LMEAuth::$userinfo;

            $session_hash = $auth->getSessionHash();
            $auth_result = $auth->logout( $session_hash );

            if ($auth_result) {
                unsetcookie( LMEConfig::get_authconfig()->__get('cookie_name') );
                setcookie( LMEConfig::get_config()->get('cookies/last_logged_user') , $userinfo['email']);
                $html_callback = '/';
            }
        } else {
            $html_callback = '/auth/login';
        }
        redirect($html_callback);
        break;
    }

    case 'action:register': {
        if ($is_logged_in) {
            redirect('/');
        }
        $additional_fields = array(
            'username'      =>  at($_POST, 'register:data:username', "Anonymous" ),
            'gender'        =>  at($_POST, 'register:data:gender', 'N'),
            'city'          =>  at($_POST, 'register:data:city', '')
        );
        $auth_result = $auth->register(
            $_POST['register:data:email'],
            $_POST['register:data:password'],
            $_POST['register:data:password_again'],
            $additional_fields
        );

        if (!$auth_result['error']) {
            // no errors
            setcookie( LMEConfig::get_config()->get('cookies/last_logged_user') , $_POST['register:data:email'],  time()+60*60*5, "/" );

            $html_callback = LMEConfig::get_config()->get('auth/auto_activation') ? '/auth/login' : '/auth/activateaccount';
        } else {
            $html_callback = '/auth/register';
        }
        redirect($html_callback);

        break;
    }




    // вообще непонятно как мы сюда попали
    default: {
        redirect('/');
        break;
    }


} // switch

if ($template_file === '*json') {
    $html = json_encode($template_data);
} elseif ($template_file !== '') {
    $html = websun_parse_template_path($template_data, $template_file, PATH_TEMPLATES);
} else {
    $html = '';
}

echo $html;
 
