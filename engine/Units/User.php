<?php
/**
 * User: Arris
 *
 * Class User
 * Namespace: Livemap
 *
 * Date: 14.10.2018, time: 13:59
 */

namespace LME\Units;

use Arris\Template;
use Arris\Auth;

/**
 * Class User
 * @package LME\Units
 *
 * Модель, реализует методы регистрации, авторизации, редактирования профиля, удаления и прочие действия с
 * учетной записью пользователя
 *
 */
class User
{
    /**
     * Naming:
     *
     * do -- view | callback-action
     *
     * where -- page | ajax | null (for callback)
     *
     * what -- register, login, etc
     *
     */


    /**
     * Рисует: страницу формы регистрации
     *
     * @return string
     */
    public function view_page_register() {
        $t = new Template('form.register.html', '$/templates/auth');
        $t->set('strong_password_required', Auth::get('password_min_score'));
        return $t->render();
    }

    /**
     * Коллбэк: регистрация
     */
    public function action_register(){
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

    }

    /**
     * Рисует: аякс форму логина
     *
     * @return string
     */
    public function view_ajax_login(){
        $t = new Template('ajax.login.html', '$/templates/auth');
        $t->set('last_login', $_COOKIE[ \Arris\Config::get('auth/cookies/last_logged_user') ] ?? '');
        return $t->render();
    }

    /**
     * Коллбэк: логин
     *
     * @return string
     */
    public function action_login(){
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

    }

    /**
     * Рисует: аякс форму логаута
     *
     * @return string
     */
    public function view_ajax_logout(){
        $t = new Template('ajax.logout.html', '$/templates/auth');
        $userinfo = Auth::getInstance()->getCurrentSessionUserInfo();
        $t->set('/', [
            'is_logged_user' => $userinfo['email'],
            'is_logged_user_ip' => $userinfo['ip']
        ]);
        return $t->render();
    }

    /**
     * Коллбэк: логаут
     *
     * @return string
     */
    public function action_logout(){
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
    }

    /**
     * Рисует: страницу редактирования профиля
     *
     * @return string
     */
    public function view_page_profile(){
        $t = new Template('form.profile.html', '$/templates/auth');

        $userinfo = Auth::getInstance()->getCurrentUser();

        $t->set('/', [
            'username'      =>  $userinfo['username'],
            'gender'        =>  $userinfo['gender'],
            'city'          =>  $userinfo['city'],
            'current_email' =>  $userinfo['email'],
            'strong_password'=> Auth::get('password_min_score'),
        ]);

        return $t->render();
    }



}