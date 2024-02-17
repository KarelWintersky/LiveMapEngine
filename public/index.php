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

    AppRouter::get('/', 'PagesController@view_frontpage', 'view.frontpage');
    AppRouter::get('/map/{id:[\w\.]+}[/]', 'MapsController@view_map_fullscreen', 'view.map.fullscreen');
    AppRouter::get('/map:iframe/{id:[\w\.]+}[/]', 'MapsController@view_iframe', 'view.map.iframe');
    AppRouter::get('/map:folio/{id:[\w\.]+}[/]', 'MapsController@view_map_folio', 'view.map.folio');
    AppRouter::get('/map:js/{id:[\w\.]+}.js', 'MapsController@view_js_map_definition');

    // AppRouter::get('/123', [ \Livemap\Controllers\PagesController::class, 'view_frontpage']);

    AppRouter::get('/region/get', 'RegionsController@view_region_info', 'view.region.info');

    // логин-логаут
    AppRouter::get('/auth/login', 'AuthController@view_form_login', 'view.form.login');
    AppRouter::post('/auth/login', 'AuthController@callback_login', 'callback.form.login');
    AppRouter::get('/auth/logout', 'AuthController@callback_logout', 'view.form.logout');

    // Для доступа к роутам этой группы пользователь должен быть НЕ залогинен
    // Это проверяет метод AuthMiddleware@check_not_logged_in
    // Если пользователь залогинен - делается редирект в корень
    AppRouter::group(
        [
            'before'    =>  '\Livemap\Middlewares\AuthMiddleware@check_not_logged_in'
        ], static function() {
            // Регистрация
            AppRouter::get('/auth/register', 'AuthController@view_form_register', 'view.form.register');
            AppRouter::post('/auth/register', 'AuthController@callback_register', 'callback.form.register');

            // Активация аккаунта, заготовка
            AppRouter::get('/auth/activate', 'AuthController@callback_activate_account');

            // Восстановить пароль, заготовки
            AppRouter::get('/auth/recover', 'AuthController@view_form_recover_password', 'view.auth.recover.form'); // форма восстановления пароля
            AppRouter::post('/auth/recover', 'AuthController@callback_recover_password'); // обработчик формы, шлет запрос на почту
            AppRouter::get('/auth/reset', 'AuthController@view_form_new_password'); // принимает ключ сброса пароля и предлагает ввести новый
            AppRouter::post('/auth/reset', 'AuthController@callback_new_password'); // коллбэк: устанавливает новый пароль
        }
    );

    // Для доступа к роутам этой группы пользователь должен быть ЗАЛОГИНЕН
    // Это проверяет посредник
    // AuthMiddleware@check_is_logged_in
    // Если проверка неудачна - кидается исключение AccessDeniedException
    AppRouter::group(
        [
            'before'    =>  '\Livemap\Middlewares\AuthMiddleware@check_is_logged_in'
        ], static function() {
            // редактировать профиль (должно быть в группе "залогинен")
            AppRouter::get('/user/profile', 'UsersController@view_form_profile', 'view.user.profile'); // показать текущий профиль
            AppRouter::post('/user/profile:update', 'UsersController@callback_profile_update', 'callback.user.profile.update'); // обновить текущий профиль

            // редактировать регион: форма и коллбэк
            AppRouter::get('/region/edit', 'RegionsController@view_region_edit_form', 'edit.region.info');
            AppRouter::post('/region/edit', 'RegionsController@callback_update_region', 'update.region.info');
        }
    );

    // админские роуты
    // Роуты этой группы доступны только СУПЕРАДМИНИСТРАТОРУ
    // Проверяет посредник AuthMiddleware@check_is_admin_logged
    // иначе кидается исключение AccessDeniedException
    AppRouter::group(
        [
            'before'    =>  '\Livemap\Middlewares\AuthMiddleware@check_is_admin_logged',
            'prefix'    =>  '/admin'
        ], static function() {
            AppRouter::get('[/]',           [\Livemap\Controllers\AdminController::class, 'view_main_page'], 'admin.main.page'); // можно пустую строчку, но я добавил необязательный элемент и убираю его регуляркой в роутере
            AppRouter::get('/users/list',   [\Livemap\Controllers\AdminController::class, 'view_list_users'], 'admin.users.view.list');
            AppRouter::get('/users/create', [\Livemap\Controllers\AdminController::class, 'form_create_user' ], 'admin.users.view.create');
            AppRouter::post('/users/insert', [\Livemap\Controllers\AdminController::class, 'callback_insert'], 'admin.users.callback.insert');
            AppRouter::get('/users/edit',   [\Livemap\Controllers\AdminController::class, 'form_edit_user' ], 'admin.users.view.edit');
            AppRouter::post('/users/update', [\Livemap\Controllers\AdminController::class, 'callback_update'], 'admin.users.callback.update');
            AppRouter::get('/users/delete', [\Livemap\Controllers\AdminController::class, 'callback_delete'], 'admin.users.callback.delete');

            // редактирование списка карт?
            AppRouter::get('/maps/list', [\Livemap\Controllers\AdminController::class, 'view_list_maps' ], 'admin.maps.view.list');
            AppRouter::get('/maps/create', [\Livemap\Controllers\AdminController::class, 'view_map_create' ], 'admin.maps.view.create');
            AppRouter::post('/maps/insert', [\Livemap\Controllers\AdminController::class, 'callback_map_insert' ], 'admin.maps.callback.insert');
            AppRouter::post('/maps/upload', [\Livemap\Controllers\AdminController::class, 'callback_map_upload' ], 'admin.maps.callback.upload');

            // Прочие

            // права доступа к картам?

            // присвоение карте владельца (связь owner - map)
            // права доступа к карте
    });

    App::$template->assign("routing", AppRouter::getRoutersNames());

    /**********
     * END *
     *********/
    AppRouter::dispatch();

    App::$template->assign("title", App::$template->makeTitle(" &mdash;"));

    App::$template->assign("flash_messages", json_encode( App::$flash->getMessages() ));

    App::$template->assign("_auth", \config('auth'));
    App::$template->assign("_config", \config());
    App::$template->assign("_request", $_REQUEST);

} catch (\Livemap\Exceptions\AccessDeniedException $e) {

    AppLogger::scope('access.denied')->notice($e->getMessage(), [ $_SERVER['REQUEST_URI'], config('auth.ipv4') ] );
    App::$template->assign('message', $e->getMessage());
    App::$template->setTemplate("_errors/403.tpl");

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

