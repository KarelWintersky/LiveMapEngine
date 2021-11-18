<?php

use Arris\AppLogger;
use Arris\DB;
use Dotenv\Dotenv;
use Livemap\App;
use Livemap\Template;
use Livemap\Units\Auth;
use Pecee\SimpleRouter\SimpleRouter;

define('__PATH_ROOT__', dirname(__DIR__, 1));
define('__PATH_CONFIG__', __PATH_ROOT__ . '/config/');
require_once __PATH_ROOT__ . '/vendor/autoload.php';

try {
    Dotenv::create( __PATH_CONFIG__, 'common.conf' )->load();

    $app = App::factory();

    AppLogger::init('Livemap', bin2hex(random_bytes(8)), [
        'default_logfile_path'      => __PATH_ROOT__ . 'logs/',
        'default_logfile_prefix'    => '/' . date_format(date_create(), 'Y-m-d') . '__'
    ] );
    
    DB::init(NULL, [
        'hostname'          =>  getenv('DB.HOST'),
        'database'          =>  getenv('DB.NAME'),
        'username'          =>  getenv('DB.USERNAME'),
        'password'          =>  getenv('DB.PASSWORD'),
        'port'              =>  getenv('DB.PORT'),
        'charset'           =>  'utf8mb4',
        'charset_collate'   =>  'utf8mb4_general_ci',
    ], AppLogger::scope('pdo'));
    
    $app->set('pdo', DB::getConnection());
    $app->pdo = DB::getConnection();
    
    Auth::init($app->pdo);

    $SMARTY = new Smarty();
    $SMARTY->setTemplateDir( getenv('PATH.SMARTY_TEMPLATES') );
    $SMARTY->setCompileDir( getenv('PATH.SMARTY_CACHE') );
    $SMARTY->setForceCompile(true);

    Template::init( $SMARTY );
    
    //@todo: добавить логгирование
    //@todo: добавить Auth + PHPAuth

    /*AppRouter::init(AppLogger::addScope('router'));
    AppRouter::setDefaultNamespace('\EcoParser');
    AppRouter::dispatch();*/

    SimpleRouter::setDefaultNamespace('Livemap\Controllers');
    SimpleRouter::get('/', 'PagesController@view_page_frontpage')->name('page.frontpage');

    SimpleRouter::group(['middleware' => \Livemap\Middlewares\AuthAvailableForGuests::class], function (){
        SimpleRouter::get('/auth/register', 'UsersController@view_page_register');
        SimpleRouter::post('/auth/action:register', 'UsersController@callback_action_register');

        SimpleRouter::get('/auth/login', 'UsersController@view_ajax_login');
        SimpleRouter::post('/auth/ajax:login', 'UsersController@callback_action_login');
    });

    SimpleRouter::group(['middleware' => \Livemap\Middlewares\AuthAvailableForLogged::class], function (){
        SimpleRouter::get('/auth/logout', 'UsersController@view_ajax_logout');
        SimpleRouter::post('/auth/action:logout', 'UsersController@callback_action_logout');

        SimpleRouter::get('/auth/profile', 'UsersController@view_page_profile');

        SimpleRouter::get('/edit/region/{map_alias}/{region_id}', 'RegionsController@view_page_edit_region');
        SimpleRouter::post('/edit/region/{map_alias}/{region_id}', 'RegionsController@callback_page_edit_region');
    });

    SimpleRouter::group([
        'where'     =>  ['map_alias' => '[\w\d\.]+'],
        'middleware'=>  \Livemap\Middlewares\MapIsAccessibleMiddleware::class
    ], function (){
        SimpleRouter::get('/map/{map_alias}', 'MapsController@view_map_fullscreen');

        SimpleRouter::get('/map:iframe/{map_alias}', 'MapsController@view_map_iframe');

        SimpleRouter::get('/map:folio/{map_alias}', 'MapsController@view_map_folio');

        // получить информацию по региону
        SimpleRouter::get('/api/get/regiondata', 'RegionsController@view_region_info'); // /api/getRegionData/{map_alias}/{region_id}

        // получить JS-файл описания разметки
        SimpleRouter::get('/js/map/{map_alias}.js', 'MapsController@get_js_map_definition');
    });

    SimpleRouter::start();

} catch (Exception $e) {
    dump($e->getMessage());
}
