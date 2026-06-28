<?php

use App\App;
use App\Controllers\AuthController;
use App\Controllers\UsersController;
use App\Units\TemplateHelper;
use Arris\AppLogger;
use Arris\AppRouter;
use Arris\Exceptions\AppRouterNotFoundException;

define('__PATH_ROOT__', dirname(__DIR__, 1));
define('__PATH_CONFIG__', '/etc/arris/livemap/');

if (!session_id()) @session_start();

try {
    if (!is_file(__PATH_ROOT__ . '/vendor/autoload.php')) {
        throw new RuntimeException("[FATAL ERROR] No 3rd-party libraries installed.");
    }
    require_once __PATH_ROOT__ . '/vendor/autoload.php';

    $app = App::factory(['/etc/arris/livemap/config.yaml']);

    App::init();

    App::initErrorHandler();

    App::initLogger();

    App::initPresenter();

    App::initMobileDetect();

    App::initDatabase();

    App::initAuth();

    App::initRedis();

    /**
     * End bootstrap
     * Routing
     */

    AppRouter::init(AppLogger::scope('router'));
    AppRouter::setDefaultNamespace('\App\Controllers');

    /**********
     * ROUTES *
     *********/

    // публичный показ карты

    AppRouter::get('/',                             [\App\Controllers\PagesController::class, 'view_frontpage'],         'view.frontpage');

    AppRouter::get('/map/{map_alias:[\w\.]+}[/]',          [\App\Controllers\MapsController::class, 'view_map_fullscreen'],    'view.map.fullscreen');
    AppRouter::get('/map:js/{map_alias:[\w\.]+}.js',       [\App\Controllers\JSController::class, 'view_js_map_definition'],  'view.map.js');

    // роуты для дополнительного функционала карт
    AppRouter::get('/map:iframe/{map_alias:[\w\.]+}[/]',   [\App\Controllers\MapsController::class, 'view_iframe'],            'view.map.iframe');
    AppRouter::get('/map:folio/{map_alias:[\w\.]+}[/]',    [\App\Controllers\MapsController::class, 'view_map_folio'],         'view.map.folio');

    // роут получения информации о регионе на карте
    AppRouter::get('/region/get', [ \App\Controllers\RegionsController::class, 'view_region_info'], 'view.region.info');

    // роут получения информации о карте
    AppRouter::get('/map:about/', [ \App\Controllers\MapsController::class, 'view_map_about'], 'view.map.about');

    // о проекте
    AppRouter::get('/about', [ \App\Controllers\PagesController::class, 'view_about'], 'view.about');

    // проекты
    AppRouter::get('/project/{id:[\w]+}[/]', [ \App\Controllers\ProjectsController::class, 'view_project'], 'view.project');

    // логин-логаут
    AppRouter::get('/auth/login', [\App\Controllers\AuthController::class, 'view_form_login'], 'view.form.login');
    AppRouter::post('/auth/login', [\App\Controllers\AuthController::class, 'callback_login'], 'callback.form.login');
    AppRouter::get('/auth/logout', [\App\Controllers\AuthController::class, 'callback_logout'], 'view.form.logout');

    /**
     * Для доступа к роутам этой группы пользователь должен быть НЕ залогинен
     * Это проверяет метод AuthMiddleware@check_not_logged_in
     * Если пользователь залогинен - делается редирект в корень
     */
    AppRouter::group(
        before: [ \App\Middlewares\AuthMiddleware::class, 'check_not_logged_in'],
        callback: function() {
            AppRouter::get('/auth/register', [AuthController::class, 'view_form_register'], 'view.form.register');
            AppRouter::post('/auth/register', [AuthController::class, 'callback_register'], 'callback.form.register');

            // Активация аккаунта, заготовка
            AppRouter::get('/auth/activate', [AuthController::class, 'callback_activate_account']);

            // Восстановить пароль, заготовки
            AppRouter::get('/auth/recover', [AuthController::class, 'view_form_recover_password'], 'view.auth.recover.form');
            AppRouter::post('/auth/recover', [AuthController::class, 'callback_recover_password']);
            AppRouter::get('/auth/reset', [AuthController::class, 'view_form_new_password']);
            AppRouter::post('/auth/reset', [AuthController::class, 'callback_new_password']);
        }
    );

    /**
     * Для доступа к роутам этой группы пользователь должен быть ЗАЛОГИНЕН
     * Это проверяет посредник AuthMiddleware@check_is_logged_in
     * Если проверка неудачна - кидается исключение AccessDeniedException
     */
    AppRouter::group(
        before: [\App\Middlewares\AuthMiddleware::class, 'check_is_logged_in'],
        callback: function() {
            // редактировать профиль (должно быть в группе "залогинен")
            AppRouter::get('/user/profile', [UsersController::class, 'view_form_profile'], 'view.user.profile'); // показать текущий профиль
            AppRouter::post('/user/profile:update', [UsersController::class, 'callback_profile_update'], 'callback.user.profile.update'); // обновить текущий профиль

            // пользуемся тем, что map_alias передается двумя путями: `POST edit:alias:map` или `GET map`
            AppRouter::group(
                before: [ \App\Middlewares\AuthMiddleware::class, 'check_can_edit'],
                callback: function(){

                    AppRouter::get('/region/edit', [\App\Controllers\RegionsController::class, 'view_region_edit_form'], 'edit.region.info');
                    AppRouter::post('/region/edit', [\App\Controllers\RegionsController::class, 'callback_update_region'], 'update.region.info');

                    // редактирование ABOUT карты
                    AppRouter::get('/map/edit:about', [ \App\Controllers\MapsController::class, 'view_map_edit_form'], 'edit.map.about');
                    AppRouter::post('/map/edit:about', [ \App\Controllers\MapsController::class, 'callback_update_map'], 'update.map.about');
                }
            );
        }
    );

    //
    //
    //
    //

    /**
     * Админские роуты.
     *
     * Роуты этой группы доступны только СУПЕРАДМИНИСТРАТОРУ
     * Проверяет посредник AuthMiddleware@check_is_admin_logged
     * иначе кидается исключение AccessDeniedException
     */
    AppRouter::group(
        prefix: '/admin',
        before: [ \App\Middlewares\AuthMiddleware::class, 'check_is_admin_logged'],
        callback: function() {
            AppRouter::get('[/]',           [\App\Controllers\AdminController::class, 'view_main_page'], 'admin.main.page'); // можно пустую строчку, но я добавил необязательный элемент и убираю его регуляркой в роутере
            AppRouter::get('/users/list',   [\App\Controllers\AdminController::class, 'view_list_users'], 'admin.users.view.list');
            AppRouter::get('/users/create', [\App\Controllers\AdminController::class, 'form_create_user' ], 'admin.users.view.create');
            AppRouter::post('/users/insert', [\App\Controllers\AdminController::class, 'callback_insert'], 'admin.users.callback.insert');
            AppRouter::get('/users/edit',   [\App\Controllers\AdminController::class, 'form_edit_user' ], 'admin.users.view.edit');
            AppRouter::post('/users/update', [\App\Controllers\AdminController::class, 'callback_update'], 'admin.users.callback.update');
            AppRouter::get('/users/delete', [\App\Controllers\AdminController::class, 'callback_delete'], 'admin.users.callback.delete');

            // редактирование списка карт (разве что публичного списка...)
            //@todo: возможно, на данном этапе лучше засасывать конфиги карт в БД и редис и брать данные оттуда. А при обновлении дефиниций вызывать
            //toolkit-script типа reimport maps...
            AppRouter::get('/maps/list', [\App\Controllers\AdminController::class, 'view_list_maps' ], 'admin.maps.view.list');
            AppRouter::get('/maps/create', [\App\Controllers\AdminController::class, 'view_map_create' ], 'admin.maps.view.create'); //@todo: view_manage_map & admin.map.view.form
            AppRouter::post('/maps/insert', [\App\Controllers\AdminController::class, 'callback_map_insert' ], 'admin.maps.callback.insert');
            AppRouter::post('/maps/upload', [\App\Controllers\AdminController::class, 'callback_map_upload' ], 'admin.maps.callback.upload'); //@todo: а не update ?

            // Работа с проектами
            AppRouter::get('/projects/list', [\App\Controllers\AdminController::class, 'view_list_projects'], 'admin.projects.view.list');
            AppRouter::get('/projects/create', [\App\Controllers\AdminController::class, 'view_manage_project'], 'admin.projects.view.form');
            AppRouter::post('/projects/insert', [\App\Controllers\AdminController::class, 'callback_project_insert'], 'admin.projects.callback.insert');
            AppRouter::post('/projects/update', [\App\Controllers\AdminController::class, 'callback_project_update'], 'admin.projects.callback.update');

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

    App::$template->assign("title", TemplateHelper::makeTitle(" &mdash;"));

    App::$template->assign("flash_messages", json_encode( App::$flash->getMessages() ));

    App::$template->assign("_auth", App::config('auth'));
    App::$template->assign("_config", App::getInstance()->getConfig());
    App::$template->assign("_request", $_REQUEST);

} catch (\App\Exceptions\AccessDeniedException $e) {

    AppLogger::scope('access.denied')->notice($e->getMessage(), [ $_SERVER['REQUEST_URI'], App::config('auth.ipv4') ] );
    App::$template->assign('message', $e->getMessage());
    App::$template->setTemplate("_errors/403.tpl");

} catch (AppRouterNotFoundException $e) {

    AppLogger::scope('main')->notice("AppRouter::NotFound", [ $e->getMessage(), $e->getInfo() ] );
    http_response_code(404);
    App::$template->setTemplate("_errors/404.tpl");
    App::$template->assign("message", $e->getMessage());

}/* catch (\RuntimeException|\Exception $e) {
// Пока не внедрим кастомную страницу для Kuria + логгирование там же
// для прода этот блок надо раскомментировать
// для дева - закомментировать (чтобы исключения ловила курия)
// пока что кастомная страницы Курии НИКАКАЯ (и не ведет логи)

    AppLogger::scope('main')->notice("Runtime Error", [ $e->getMessage() ] );
    http_response_code(500);
    App::$template->setTemplate("_errors/500.tpl");
    App::$template->assign("message", $e->getMessage());

    if (getenv('IS.PRODUCTION') == 0) {
        echo "<h1>(RUNTIME) EXCEPTION</h1>";
        echo "<h3>_REQUEST</h3>";
        d($_REQUEST);
        echo "<h3>REQUEST_URI</h3>";
        d($_SERVER['REQUEST_URI']);
        echo "<h3>EXCEPTION DUMP</h3>";
        \Arris\Util\Debug::ddt($e->getTrace());
        dd($e);
    }
}*/


$render = App::$template->render();
if ($render) {
    echo $render;
}

logSiteUsage( AppLogger::scope('site_usage') );

if (App::$template->isRedirect()) {
    App::$template->makeRedirect();
}

