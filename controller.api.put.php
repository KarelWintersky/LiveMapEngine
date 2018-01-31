<?php
/**
 * User: Arris
 * Date: 24.09.2017, time: 16:07
 */

define('__ROOT__', __DIR__);
require_once (__ROOT__ . '/engine/__required.php');

// CSRF token!

$alias_map = $_POST['edit:alias:map'] ?? NULL;
$id_region = $_POST['edit:id:region'] ?? NULL;

$lm_engine = new LiveMapEngine( LMEConfig::get_dbi() );

$current_role = $lm_engine->ACL_getRole($alias_map);
$can_edit = $lm_engine->ACL_isValidRole($current_role, 'EDITOR');

$auth = LMEAuth::$instance;

if (!$can_edit) {
    die('Not enough access rights for update info!');
}

if (!($alias_map && $id_region)) {
    die('Hacking attempt!');
}

switch ($_GET['target']) {
    case 'regiondata': {
        // вызвано из формы редактирования. Нужно попытаться сохранить контент (проверив права) и отдать json-ответ для исходной страницы.
        // на ней отобразиться "сохраняем, спиннер и либо будет редирект через 3 секунды, либо сообщение об ошибке

        $is_excludelists = filter_array_for_allowed($_POST, 'edit:is:excludelists', [
            'N', 'F', 'A'
        ], 'N');

        $is_publicity = filter_array_for_allowed($_POST, 'edit:is:publicity', [
            "ANYONE", "VISITOR", "EDITOR", "OWNER", "ROOT"
        ], 'ANYONE');

        // проверяем права редактирования
        // LiveMapEngine->checkACL( $auth->getCurrentUID(),  $edit_map_alias) // должно быть editor или owner
        $data = [
            'id_map'        =>  0,
            'alias_map'     =>  $alias_map,

            // Кто редактировал (айди пользователя)
            'edit_whois'    =>  LMEAuth::$uid,

            // данные по региону
            'id_region'     =>  $id_region,

            'title'         =>  $_POST['edit:region:title'],
            'content'       =>  $_POST['edit:region:content'],
            'edit_comment'  =>  $_POST['edit:region:comment'],

            // настройки видимости регионов
            'is_excludelists'   =>  $is_excludelists,
            'is_publicity'      =>  $is_publicity
        ];

        $template_data = $lm_engine->storeMapRegionData($data);

        if ($template_data['state']) {
            unsetcookie( LMEConfig::get_mainconfig()->get('cookies/filemanager_current_map') );
            unsetcookie( LMEConfig::get_mainconfig()->get('cookies/filemanager_storage_path') );
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