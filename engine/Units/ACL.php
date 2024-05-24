<?php

namespace Livemap\Units;

use Livemap\App;
use PDO;

class ACL extends \Livemap\AbstractClass
{
    const USERID_SUPERADMIN = 1;

    const ROLE_TO_POWER = [
        'ANYONE'        =>  0,
        'VISITOR'       =>  4,
        'EDITOR'        =>  16,
        'OWNER'         =>  64,
        'ROOT'          =>  1024
    ];

    /**
     * Временная проверка возможности редактирования регионов на карте
     *
     * Делается на основе списка админских емейлов и списка емейлов в поле `can_edit` определения карты
     *
     * @todo: передавать первым аргументом конфиг карты
     *
     * @param $map_alias
     * @return bool
     */
    public static function simpleCheckCanEdit($map_alias)
    {
        $map = (new MapConfig($map_alias))->loadConfig()->getConfig();
        $admin_emails = getenv('AUTH.ADMIN_EMAILS') ? explode(' ', getenv('AUTH.ADMIN_EMAILS')) : [];
        $allowed_editors = array_merge($map->can_edit ?? [], $admin_emails);

        return !is_null(config('auth.email')) && in_array(config('auth.email'), $allowed_editors);
    }

    /**
     * Простая проверка роли
     * ВАЖНО: пользователь с идентификатором 1 может ВСЁ ВСЕГДА
     *
     * @param $user_id
     * @param $map_alias
     * @param string $role
     * @return bool
     * @throws \Exception
     */
    public static function checkRole($user_id, $map_alias, $role = 'edit')
    {
        // Базовые проверки
        if (self::USERID_SUPERADMIN === $user_id) {
            return true;
        }
        if (empty($user_id)) {
            return false;
        }
        if (empty($map_alias)) {
            return false;
        }
        /**
         * @var PDO $pdo
         */
        $pdo = App::factory()->pdo;

        $sth = $pdo->prepare("SELECT :role FROM settings_acl WHERE user_id = :user_id AND map_alias = :map_alias");
        $sth->execute([
            'role'      =>  $role,
            'user_id'   =>  $user_id,
            'map_alias' =>  $map_alias
        ]);

        return ($sth && $sth->fetchColumn() === 'Y') ? true : false;
    }

    /**
     * Возврат максимальной роли для указанного пользователя и карты
     *
     * @param $user_id
     * @param $map_alias
     * @return string
     * @throws \Exception
     */
    public static function getRole($user_id, $map_alias)
    {
        if (self::USERID_SUPERADMIN === $user_id) {
            return 'OWNER';
        }

        $pdo = App::factory()->pdo;

        $sth = $pdo->prepare("SELECT user_id, owner, edit, view FROM settings_acl WHERE user_id = :user_id AND map_alias = :map_alias");
        $sth->execute([
            'user_id'   =>  $user_id,
            'map_alias' =>  $map_alias
        ]);
        $acl = $sth->fetch();

        if (!$acl) {
            return 'ANYONE';
        }

        if ($acl['owner'] === 'Y') {
            return 'OWNER';
        }

        if ($acl['edit'] === 'Y') {
            return 'EDITOR';
        }

        if ($acl['view'] === 'Y') {
            return 'VISITOR';
        }

        return 'ANYONE';
    }

    /**
     * @param $first_role
     * @param $second_role
     * @return bool
     */
    public static function isValidRole($first_role, $second_role)
    {
        if (!array_key_exists($first_role, self::ROLE_TO_POWER)) {
            return false;
        }

        if (!array_key_exists($second_role, self::ROLE_TO_POWER)) {
            return false;
        }

        return ( self::ROLE_TO_POWER[$first_role] >= self::ROLE_TO_POWER[$second_role] );
    }

}