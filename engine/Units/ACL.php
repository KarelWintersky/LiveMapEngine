<?php

namespace Livemap\Units;

use Livemap\App;
use PDO;

class ACL extends \Livemap\AbstractClass
{
    const USERID_SUPERADMIN = 1;

    const ROLE_TO_POWER = [
        'ANYONE'        =>  0,
        'VISITOR'       =>  1,
        'EDITOR'        =>  10,
        'OWNER'         =>  100,
        'ROOT'          =>  1000
    ];

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