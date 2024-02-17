<?php

namespace Livemap\Controllers;

use Livemap\App;
use Livemap\AuthRoles;
use Psr\Log\LoggerInterface;

class AdminController extends \Livemap\AbstractClass
{
    public function __construct($options = [], LoggerInterface $logger = null)
    {
        parent::__construct($options, $logger);

        $this->template->setTemplate('_admin.tpl');
    }

    public function view_main_page()
    {
        $this->template->assign("inner_template", 'admin/main.tpl');
    }

    public function view_list_users()
    {
        $this->template->assign("inner_template", 'admin/users/list.tpl');

        $sth = App::$pdo->query("SELECT * FROM {$this->tables->users} ORDER BY registered DESC ");

        $list = [];
        while ($user = $sth->fetch()) {
            $user['roles'] = AuthRoles::getRolesForUser($user['roles_mask']);
            $list[] = $user;
        }

        $this->template->assign("userlist", $list);
    }

    public function form_create_user()
    {
        $this->template->assign("inner_template", 'admin/users/manage.tpl');

    }

    public function callback_insert()
    {

    }

    public function form_edit_user()
    {
        $this->template->assign("inner_template", 'admin/users/manage.tpl');

    }

    public function callback_update()
    {

    }

    public function callback_delete()
    {

    }

    public function view_list_maps()
    {
        // список карт в БД
        $this->template->assign("inner_template", 'admin/maps/list.tpl');
    }

    public function view_map_create()
    {
        $this->template->assign("inner_template", 'admin/maps/create.tpl');
        // форма создания карты
        // коллбэки: создания и импортирования
    }

    public function callback_map_insert()
    {
        // форма создания карты
        // коллбэки: создания и импортирования
    }

    public function callback_map_upload()
    {
        // форма создания карты
        // коллбэки: создания и импортирования
    }

    public function view_map_edit()
    {
        $this->template->assign("inner_template", 'admin/maps/edit.tpl');
    }


}