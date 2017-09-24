<?php
/**
 * User: Arris
 * Date: 24.09.2017, time: 18:18
 */
define('__ROOT__', __DIR__);

require_once (__ROOT__ . '/engine/__required.php');
$auth = LMEConfig::get_auth();

$is_logged_in = $auth->isLogged(); // true if logged-in

$template_file = '';
$template_data = array();

switch ($_GET['action']) {
    // форма входа
    case 'login': {
        if ($is_logged_in) {
            $template_file = 'auth.callback.instant_to_root.html';
            redirect('/');
        } else {
            $template_data['new_username'] = $_COOKIE['kw_livemap_last_logged_user'] ?? '';
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

    case 'profile': {
        if ($is_logged_in) {
            // загрузить в переменные значения из базы и вставить их в темплейт
            $userid = $auth->getSessionUID( $auth->getSessionHash() );
            $userdata = $auth->getUser($userid);

            $template_data = array(
                'username'      =>  $userdata['username'],
                'gender'        =>  $userdata['gender'],
                'city'          =>  $userdata['city'],
                'current_email' =>  $userdata['email'],
                'strong_password'=> LMEConfig::get_authconfig()->verify_password_strong_requirements
            );


            $template_file = 'auth/form.profile.html';
        } else {
            $template_file = 'auth.callback.instant_to_root.html';
            redirect('/');
        }
        break;
    }

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
    case 'action:login': {
        $auth_result = $auth->login(
            $_POST["auth:login_email"],
            $_POST["auth:login_password"],
            at($_POST, "auth:login_remember_me", 0) );

        if (!$auth_result['error']) {
            // no errors
            setcookie(LMEConfig::get_authconfig()->__get('cookie_name'), $auth_result['hash'], time()+$auth_result['expire'], "/");
            unsetcookie('kw_livemap_new_registred_username');

            $html_callback = '/';
        } else {
            $html_callback = '/auth/login';
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

$html = websun_parse_template_path($template_data, $template_file, '$/templates');

echo $html;
 
