<?php

use Arris\AppLogger;
use Arris\AppRouter;
use Dotenv\Dotenv;
use Livemap\App;

define('__PATH_ROOT__', dirname(__DIR__, 1));
define('__PATH_CONFIG__', '/etc/arris/livemap/');

if (!session_id()) @session_start();

try {
    if (!is_file(__PATH_ROOT__ . '/vendor/autoload.php')) {
        throw new RuntimeException("[FATAL ERROR] No 3rd-party libraries installed.");
    }
    require_once __PATH_ROOT__ . '/vendor/autoload.php';

    Dotenv::create( __PATH_CONFIG__, 'common.conf' )->load();

    $app = App::factory();

    App::init();

    App::initErrorHandler();

    App::initLogger();

    App::initTemplate();

    App::initMobileDetect();

    App::initDBConnection();

    App::initAuth();

    /**
     * End bootstrap
     * Routing
     */

    AppRouter::init(AppLogger::addScope('router'));
    AppRouter::setDefaultNamespace('\Livemap\Controllers');

    /**********
     * ROUTES *
     *********/

    // публичный показ карты

    AppRouter::get('/', 'PagesController@view_frontpage', 'page.frontpage');
    AppRouter::get('/map/{id:[\w\.]+}[/]', 'MapsController@view_map_fullscreen', 'view.map.fullscreen');
    AppRouter::get('/map:iframe/{id:[\w\.]+}[/]', 'MapsController@view_iframe', 'view.map.iframe');
    AppRouter::get('/map:folio/{id:[\w\.]+}[/]', 'MapsController@view_map_folio', 'view.map.folio');
    AppRouter::get('/map:js/{id:[\w\.]+}.js', 'MapsController@view_js_map_definition');

    AppRouter::get('/region/get', 'RegionsController@view_region_info', 'view.region.info');

    AppRouter::get('/auth/login', 'AuthController@view_form_login');
    AppRouter::post('/auth/login', 'AuthController@callback_login');
    AppRouter::get('/auth/logout', 'AuthController@callback_logout');

    AppRouter::group(
        [
            // не залогинен
        ], static function() {
            // Регистрация
            AppRouter::get('/auth/register', 'UsersController@view_form_register');
            AppRouter::post('/auth/register', 'UsersController@callback_register');

            // Активация аккаунта, заготовка
            AppRouter::get('/auth/activate', 'UsersController@callback_activate_account');

            // Восстановить пароль (не реализованы)
            AppRouter::get('/auth/recover', 'UsersController@view_form_recover_password'); // форма восстановления пароля
            AppRouter::post('/auth/recover', 'UsersController@callback_recover_password'); // обработчик формы, шлет запрос на почту
            AppRouter::get('/auth/reset', 'UsersController@view_form_new_password'); // принимает ключ сброса пароля и предлагает ввести новый
            AppRouter::post('/auth/reset', 'UsersController@callback_new_password'); // коллбэк: устанавливает новый пароль
        }
    );

    AppRouter::group(
        [
            // залогинен
        ], static function() {
            // редактировать профиль (должно быть в группе "залогинен")
            AppRouter::get('/users/profile', 'UsersController@view_form_profile'); // показать текущий профиль
            AppRouter::post('/users/profile:update', 'UsersController@callback_profile_update'); // обновить текущий профиль

            // редактировать регион: форма и коллбэк
            AppRouter::get('/region/edit', 'RegionsController@view_region_edit_form', 'edit.region.info');
            AppRouter::post('/region/edit', 'RegionsController@callback_update_region', 'update.region.info');
        }
    );

    /**********
     * END *
     *********/
    AppRouter::dispatch();

    App::$template->assign("title", App::$template->makeTitle(" &mdash;"));

    App::$template->assign("flash_messages", json_encode( App::$flash->getMessages() ));

    App::$template->assign("_auth", \config('auth'));
    App::$template->assign("_config", \config());
    App::$template->assign("_request", $_REQUEST);

} catch (\RuntimeException|\Exception $e) {
    // \Arris\Util\Debug::dump($e);
    d($_REQUEST);
    d($_SERVER['REQUEST_URI']);
    dd($e);
}

/*  catch (AppRouterHandlerError $e) {

    AppLogger::scope('main')->error("AppRouter::InvalidRoute", [ $e->getMessage(), $e->getInfo() ] );
    http_response_code(500);

} catch (AppRouterNotFoundException $e) {

    AppLogger::scope('main')->notice("AppRouter::NotFound", [ $e->getMessage(), $e->getInfo() ] );
    http_response_code(404);
    App::$template->setTemplate("_errors/404.tpl");

} catch (AppRouterMethodNotAllowedException $e){

    AppLogger::scope('main')->error("AppRouter::NotAllowed", [ $e->getMessage(), $e->getInfo() ] );
    http_response_code(405);

} catch (\AjurMedia\MediaBox\Exceptions\AccessDeniedException $e) {

    AppLogger::scope('access.denied')->notice($e->getMessage(), [ $_SERVER['REQUEST_URI'], config('auth.ipv4') ] );
    App::$template->assign('message', $e->getMessage());
    App::$template->setTemplate("_errors/403.tpl");

} catch (\PDOException|\RuntimeException|\JsonException|SmartyException|\Exception $e) {
    AppLogger::scope('main')->error("Other exception", [ $e->getMessage(), $e->getFile(), $e->getLine() ]);
    http_response_code(500);

    App::$template->assign('message', $e->getMessage());
    App::$template->setTemplate("_errors/500.tpl");
}*/

$render = App::$template->render();
if ($render) {
    echo $render;
}

logSiteUsage( AppLogger::scope('site_usage') );

if (App::$template->isRedirect()) {
    App::$template->makeRedirect();
}

