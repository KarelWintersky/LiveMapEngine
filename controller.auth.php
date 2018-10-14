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
 
