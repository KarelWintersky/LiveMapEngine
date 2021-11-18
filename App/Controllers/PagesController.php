<?php

namespace Livemap\Controllers;

use Livemap\Auth;
use Arris\Path;
use Livemap\Template as Template;

class PagesController
{
    public function __construct()
    {
    }

    public function view_page_frontpage()
    {
        // $auth = Auth::getInstance();
        // $userinfo = $auth->getCurrentSessionUserInfo();

        Template::setGlobalTemplate('index.tpl');

        Template::assign('authinfo', []);

        $maps_list = [];
        $indexfile = Path::create(getenv('PATH.STORAGE'))->joinName('list.json')->toString();

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

        Template::assign('maps_list', $maps_list);

        return Template::render();
    }
}