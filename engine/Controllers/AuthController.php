<?php

namespace Livemap\Controllers;

use Arris\AppLogger;
use Arris\AppRouter;
use Arris\DelightAuth\Auth\AttemptCancelledException;
use Arris\DelightAuth\Auth\AuthError;
use Arris\DelightAuth\Auth\EmailNotVerifiedException;
use Arris\DelightAuth\Auth\InvalidEmailException;
use Arris\DelightAuth\Auth\InvalidPasswordException;
use Arris\DelightAuth\Auth\TooManyRequestsException;
use Livemap\App;
use Livemap\Exceptions\AccessDeniedException;
use Psr\Log\LoggerInterface;

/**
 * Страницы и коллбэки авторизации
 */
class AuthController extends \Livemap\AbstractClass
{
    public function __construct($options = [], LoggerInterface $logger = null)
    {
        parent::__construct($options, $logger);
    }

    /**
     * Показ формы логина
     *
     * @return void
     */
    public function view_form_login()
    {
        $this->template->setTemplate('auth/login.tpl');
    }

    /**
     * Коллбэк логина
     *
     * @return void
     */
    public function callback_login()
    {
        $expire = _env( 'AUTH.EXPIRE_TIME', 86400, 'int');

        try {
            App::$auth->login($_REQUEST['email'], $_REQUEST['password'], $expire);

            // echo 'User is logged in';

        } catch (InvalidEmailException $e) {
            throw new AccessDeniedException('Неправильный E-Mail');
        } catch (InvalidPasswordException $e) {
            throw new AccessDeniedException('Неправильный пароль');
        } catch (EmailNotVerifiedException $e) {
            throw new AccessDeniedException('E-Mail не подтвержден, либо аккаунт не активирован');
        } catch (TooManyRequestsException $e) {
            throw new AccessDeniedException('Слишком много попыток авторизации. Подождите немного');
        } catch (AttemptCancelledException|AuthError $e) {
            throw new AccessDeniedException('Другая проблема: <br>' . $e->getMessage());
        }

        $ip = config('auth.ipv4');
        AppLogger::scope('main')->debug("Logged in user {$_REQUEST['email']} from {$ip}");

        App::$flash->addMessage("success", "Успешно залогинились");

        App::$template->setRedirect(AppRouter::getRouter('admin.index') );

    }

    /**
     * Коллбэк логаута, хотя переход на него делается через GET
     *
     * @return void
     * @throws AuthError
     */
    public function callback_logout()
    {
        if (!App::$auth->isLoggedIn()) {
            die('Hacking attempt!'); //@todo: logging
        }

        $u_id = App::$auth->getUserId();
        $u_email = App::$auth->getEmail();

        App::$auth->logOut();

        AppLogger::scope('main')->debug("Logged out user {$u_id} ($u_email)");

        App::$flash->addMessage("success", "Успешно вышли из системы");

        App::$template->setRedirect( AppRouter::getRouter('view.frontpage') );
    }


    public function view_form_register()
    {
        $this->template->assign("sid", session_id());
        $this->template->setTemplate('auth/register.tpl');
    }

    /**
     * @return void
     * @throws AuthError
     * @throws InvalidEmailException
     * @throws InvalidPasswordException
     * @throws TooManyRequestsException
     * @throws \Arris\DelightAuth\Auth\UserAlreadyExistsException
     */
    public function callback_register()
    {
        try {
            if ($_REQUEST['captcha'] != $_SESSION['captcha_keystring']) {
                throw new \RuntimeException("Вы неправильно ввели надпись с картинки");
            }

            if (empty($_REQUEST['email'])) {
                throw new \RuntimeException("Вы не указали email");
            }

            if (!filter_var($_REQUEST['email'], FILTER_VALIDATE_EMAIL)) {
                throw new \RuntimeException("Вы указали не EMail");
            }

            if (empty($_REQUEST['password']) || empty($_REQUEST['password_retry'])) {
                throw new \RuntimeException("Вы не указали пароль или его повтор");
            }

            if ($_REQUEST['password'] != $_REQUEST['password_retry']) {
                throw new \RuntimeException("Пароли не совпадают");
            }

            $credentials = [
                'email'     =>  $_REQUEST['email'],
                'username'  =>  $_REQUEST['username'],
                'password'  =>  $_REQUEST['password']
            ];

            App::$auth->register(
                $credentials['email'],
                $credentials['password'],
                $credentials['username'],
                static function($selector, $token) {
                    App::$auth->confirmEmail($selector, $token);
                }
            );

            $this->template->setRedirect( AppRouter::getRouter('/'));
            App::$flash->addMessage("success", "Регистрация успешна");

        } catch (\RuntimeException $e) {
            App::$flash->addMessage("error", $e->getMessage());
            $this->template->setRedirect( AppRouter::getRouter('view.form.register') );
        }
    }

    public function view_form_recover_password()
    {
        dd('todo');
    }

}