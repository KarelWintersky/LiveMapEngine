<?php

namespace App;

class AuthRoles
{
    // Delight Auth built-in roles:
    //   CONSUMER = 1, EDITOR = 2, MANAGER = 4, ADMIN = 8
    // Custom roles use higher bit positions to avoid overlap.

    public const BANNED  = 4194304; // 2²²

    public const VIEWER  = \Arris\DelightAuth\Auth\Role::CONSUMER;
    public const EDITOR  = \Arris\DelightAuth\Auth\Role::EDITOR;
    public const CURATOR = \Arris\DelightAuth\Auth\Role::MANAGER;
    public const ADMIN   = \Arris\DelightAuth\Auth\Role::ADMIN;

    private const MAP = [
        'ADMIN'   => self::ADMIN,
        'CURATOR' => self::CURATOR,
        'EDITOR'  => self::EDITOR,
        'VIEWER'  => self::VIEWER,
        'BANNED'  => self::BANNED,
    ];

    private function __construct() {}

    public static function mapRoleToId(string $role): int
    {
        return self::MAP[$role] ?? self::BANNED;
    }

    public static function mapIdToRole(int $id): string
    {
        return (array_flip(self::MAP))[$id] ?? 'BANNED';
    }

    public static function getRolesForUser(int $rolesBitmask): array
    {
        return array_filter(
            self::MAP,
            static fn(int $mask): bool => ($rolesBitmask & $mask) === $mask,
            ARRAY_FILTER_USE_KEY
        );
    }

    public static function getMap(): array
    {
        return self::MAP;
    }
}