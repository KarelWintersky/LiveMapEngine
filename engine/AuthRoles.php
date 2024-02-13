<?php

namespace Livemap;

class AuthRoles
{
    const BANNED = 4194304;

    const VIEWER = \Arris\DelightAuth\Auth\Role::CONSUMER;

    const EDITOR = \Arris\DelightAuth\Auth\Role::EDITOR;

    const CURATOR = \Arris\DelightAuth\Auth\Role::MANAGER;

    const ADMIN = \Arris\DelightAuth\Auth\Role::ADMIN;

    private function __construct() {}

    public static function mapRoleToId($role)
    {
        switch ($role) {
            case 'ADMIN': {
                $new_role = self::ADMIN;
                break;
            }
            case 'CURATOR': {
                $new_role = self::CURATOR;
                break;
            }
            case 'EDITOR': {
                $new_role = self::EDITOR;
                break;
            }
            case 'VIEWER': {
                $new_role = self::VIEWER;
                break;
            }

            case 'BANNED': {
                $new_role = self::BANNED;
                break;
            }
            default: {
                $new_role = self::BANNED;
            }
        }

        /*$new_role = match ($_REQUEST['role']) {
            'ADMIN'     =>  Role::ADMIN,
            'CURATOR'   =>  Role::CURATOR,
            'EDITOR'    =>  Role::EDITOR,
            'VIEWER'    =>  Role::VIEWER,
            default     =>  Role::BANNED
        };*/

        return $new_role;
    }

    public static function mapIdToRole($id)
    {
        switch ($id) {
            case self::ADMIN: {
                $user_role = 'ADMIN';
                break;
            }
            case self::CURATOR: {
                $user_role = 'CURATOR';
                break;
            }
            case self::EDITOR: {
                $user_role = 'EDITOR';
                break;
            }
            case self::VIEWER: {
                $user_role = 'VIEWER';
                break;
            }
            default: {
                $user_role = 'BANNED';
            }
        }
        return $user_role;
    }

    public static function getRolesForUser($rolesBitmask)
    {
        return \array_filter(
            self::getMap(),
            static function ($each) use ($rolesBitmask) {
                return ($rolesBitmask & $each) === $each;
            },
            \ARRAY_FILTER_USE_KEY
        );
    }

    public static function getMap()
    {
        $reflectionClass = new \ReflectionClass(static::class);

        return \array_flip($reflectionClass->getConstants());
    }

}