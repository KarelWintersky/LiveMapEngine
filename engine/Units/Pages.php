<?php
/**
 * User: Arris
 *
 * Class Pages
 * Namespace: LME\Units
 *
 * Date: 14.10.2018, time: 14:03
 */

namespace LME\Units;

use Arris\Auth;
use Arris\Template;

/**
 * Class Pages
 * @package LME\Units
 *
 * Модель, реализует методы отрисовки статических страниц
 *
 */
class Pages
{

    /**
     * Frontpage ( / )
     *
     * @return string
     */
    public function view_page_frontpage() {
        $auth = Auth::getInstance();
        $userinfo = $auth->getCurrentSessionUserInfo();

        $t = new Template('index.html', '$/templates');
        $t->set('authinfo', [
            'is_logged' =>  $auth->isLogged(),
            'email'     =>  $userinfo['email'] ?? '',
            'ip'        =>  $userinfo['ip'] ?? ''
        ]);

        {
            $maps_list = [];
            $indexfile = __ROOT__ . \Arris\Config::get('storage/maps') . '/list.json';

            if (is_readable($indexfile)) {
                $json = json_decode( file_get_contents( $indexfile ) );

                foreach ($json->maps as $i => $map) {
                    $alias = $map->alias;
                    $title = $map->title;
                    $key = str_replace('.', '~', $alias);

                    $maps_list[ $key ] = [
                        'alias' =>  $alias,
                        'title' =>  $title
                    ];
                }
            }
        }

        $t->set('maps_list', $maps_list);

        return $t->render();
    }

}