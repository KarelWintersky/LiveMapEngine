<?php

namespace Livemap\Controllers;

use Livemap\Template;
use Livemap\Units\Auth;
use function DI\get;

class UsersController
{
    public function __construct()
    {
    }
    
    public function view_page_register()
    {
        Template::assign('strong_password_required', Auth::get('password_min_score'));
        return Template::render('auth/form.register.tpl');
    }

    public function callback_action_register()
    {
        $auth = Auth::getInstance();
        $additional_fields = [
            'username'  =>  _input('register:data:username', "Anonymous"),
            'gender'    =>  _input('register:data:gender', 'N'),
            'city'      =>  _input('register:data:city', '')
        ];
        $auth_result = $auth->register(
            $_POST['register:data:email'],
            $_POST['register:data:password'],
            $_POST['register:data:password_again'],
            $additional_fields
        );
        
        if (!$auth_result['error']) {
            setcookie( getenv('AUTH.COOKIES.LAST_LOGGED_USER'), $_POST['register:data:email'], time()+ getenv('AUTH.LOGGED_DURATION'), '/' );
        
            $html_callback = getenv('AUTH.AUTO_ACTIVATION') ? '/' : '/auth/activate';
        } else {
            $html_callback = '/auth/register';
        }
        _redirect($html_callback, 302);
    }

    public function view_ajax_login()
    {
        Template::assign('last_login', $_COOKIE[ getenv('AUTH.COOKIES.LAST_LOGGED_USER')] ?? '');
        return Template::render('auth/ajax.login.tpl');
    }

    public function callback_action_login()
    {
        $auth = Auth::getInstance();
        $auth_result = $auth->login(
            $_POST["auth:data:login"],
            $_POST["auth:data:password"],
            ($_POST["auth:data:remember_me"] ?? 0)
        );
        
        Template::assign('error', $auth_result['error']);
    
        if (!$auth_result['error']) {
            $cookie_name = Auth::get('cookie_name');
            setcookie( $cookie_name , $auth_result['hash'], time()+$auth_result['expire'], '/');
            Auth::unsetcookie( getenv('AUTH.COOKIES.NEW_REGISTRED_USERNAME') );
            
            $error_messages = "Login successful";
        } else {
            $error_messages = "Login error: {$auth_result['message']}";
        }
        
        //@todo: Это должно быть: Dictionary::init('ru_RU'); Dictionary::get('login/successful');
        Template::assign("error_messages", $error_messages);
        Template::setContentType('json');
    
        return Template::renderJSON();
    }

    public function view_ajax_logout()
    {
        $userinfo = Auth::getInstance()->getCurrentSessionUserInfo();
        Template::assign('is_logged_user', $userinfo['email']);
        Template::assign('is_logged_user_ip', $userinfo['ip']);
        
        return Template::render('auth/ajax.logout.tpl');
    }

    public function callback_action_logout()
    {
        $auth = Auth::getInstance();
        $userinfo = $auth->getCurrentSessionUserInfo();
    
        // Вот тут нужен middleware, проверяющий, мы вообще залогинены или нет.
        // пока что обойдемся тупой антихакерской проверкой
    
        if ($auth->isLogged()) {
            $session_hash = $auth->getCurrentSessionHash();
            $auth_result = $auth->logout($session_hash);
        
            Template::assign('error', $auth_result['error']);
        
            if ($auth_result) {
                Auth::unsetcookie( Auth::get('cookie_name') );
                setcookie( getenv('AUTH.COOKIES.LAST_LOGGED_USER'), $userinfo['email']);
                $error_messages = 'Мы успешно вышли из системы.';
            } else {
                $error_messages = 'UNKNOWN Error while logging out!';
            }
        } else {
            $error_messages = 'We are not logged in!!!';
        }
        Template::assign('error_messages', $error_messages);
        
        return Template::renderJSON();
    }

    public function view_page_profile()
    {
        $userinfo = Auth::getInstance()->getCurrentUser();
        
        Template::assign('username', $userinfo['username']);
        Template::assign('gender', $userinfo['gender']);
        Template::assign('city', $userinfo['city']);
        Template::assign('current_email', $userinfo['email']);
        Template::assign('strong_password', Auth::get('password_min_score'));
        
        return Template::render('auth/form.profile.tpl');
    }


}
