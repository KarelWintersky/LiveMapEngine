<?php
/**
 * User: Arris
 *
 * Class ACL
 * Namespace: LME\LivemapFramework
 *
 * Date: 14.10.2018, time: 20:44
 */

namespace LivemapFramework;

use Arris\DB;
use Arris\Auth;

/**
 * Class ACL
 * @package LME\LivemapFramework
 *
 * Модуль проверок прав доступа
 */
class ACL
{
    const USERID_SUPERADMIN = 1;

    const ROLE_TO_POWER = [
        /*
        'ANYONE'        =>  0,
        'VISITOR'       =>  1,
        'EDITOR'        =>  2,
        'OWNER'         =>  3,
        'ROOT'          =>  4,
        */

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
        if (self::USERID_SUPERADMIN === $user_id) return true;
        if (empty($user_id)) return false;
        if (empty($map_alias)) return false;

        $table = DB::getTablePrefix() . 'settings_acl';

        $query = "
        SELECT `{$role}` FROM {$table} WHERE `user_id` = {$user_id} AND `map_alias` = '{$map_alias}'
        ";

        $sth = DB::getConnection()->query($query);

        return ($sth && $sth->fetchColumn() == 'Y') ? true : false;
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
        if (self::USERID_SUPERADMIN === $user_id) return 'OWNER';

        $table = DB::getTablePrefix() . 'settings_acl';

        $query = "
        SELECT user_id, owner, edit, view FROM {$table} WHERE `user_id` = :user_id AND `map_alias` = :map_alias
        ";

        $sth = DB::getConnection()->prepare($query);
        $sth->execute([
            'user_id'   =>  $user_id,
            'map_alias' =>  $map_alias
        ]);
        $acl = $sth->fetch();

        if (!$acl) return 'ANYONE';

        if ($acl['owner'] == 'Y') return 'OWNER';

        if ($acl['edit'] == 'Y') return 'EDITOR';

        if ($acl['view'] == 'Y') return 'VISITOR';

        return 'ANYONE';
    }

    public static function isValidRole($first_role, $second_role)
    {
        if (!array_key_exists($first_role, self::ROLE_TO_POWER)) return false;
        if (!array_key_exists($second_role, self::ROLE_TO_POWER)) return false;

        return ( self::ROLE_TO_POWER[$first_role] >= self::ROLE_TO_POWER[$second_role] );
    }



}