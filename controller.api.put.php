<?php
/**
 * User: Arris
 * Date: 24.09.2017, time: 16:07
 */

define('__ROOT__', __DIR__);
require_once (__ROOT__ . '/engine/__required.php');

$auth = LMEConfig::get_auth();
$is_logged = $auth->isLogged();
if (!$is_logged) {
    die('Hacking attempt!');
}

switch ($_GET['target']) {
    case 'regiondata': {
        $lm_engine = new LiveMapEngine( LMEConfig::get_dbi() );

        // вызвано из формы редактирования. Нужно попытаться сохранить контент (проверив права) и отдать json-ответ для исходной страницы.
        // на ней отобразиться "сохраняем, спиннер и либо будет редирект через 3 секунды, либо сообщение об ошибке

        // проверяем права редактирования
        // LiveMapEngine->checkACL( $auth->getCurrentUID(),  $edit_map_alias) // должно быть editor или owner

        $map_alias = $_GET['map']   ?? NULL;
        $id_region = $_POST['edit:id:region'];

        $data = array(
            // получаем иначе
            'id_map'        =>  0,
            'alias_map'     =>  $_POST['edit:alias:map'],

            // Кто редактировал (айди пользователя)
            'edit_whois'    =>  $auth->getCurrentUID(),

            // данные по региону
            'id_region'     =>  $id_region,
            'title'         =>  $_POST['edit:region:title'],
            'content'       =>  $_POST['edit:region:content'],
            'edit_comment'  =>  $_POST['edit:region:comment'],
        );
        $template_data = $lm_engine->storeMapRegionData($data);

        if ($template_data['state']) {
            unsetcookie( LMEConfig::get_mainconfig()->get('auth/cookie_filemanager_current_map') );
            unsetcookie( LMEConfig::get_mainconfig()->get('auth/cookie_filemanager_storage_path') );
        }

        echo json_encode( $template_data );
        die;
        break;
    }

}

/*if ($render_type === 'html') {
    echo websun_parse_template_path( $template_data, $template_file, $template_path );
} elseif ($render_type === 'json') {
    // надо ли посылать заголовок что это JSON/AJAX ?
    echo json_encode( $template_data );
};*/