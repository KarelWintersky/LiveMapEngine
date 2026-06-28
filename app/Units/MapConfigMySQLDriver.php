<?php

namespace App\Units;

class MapConfigMySQLDriver implements MapConfigDriverInterface
{
    public function getDriverName(): string
    {
        return 'mysql';
    }

    /**
     * @throws \RuntimeException
     */
    public function load(string $map_id): array
    {
        throw new \RuntimeException("[MapConfig MySQL Driver] Not yet implemented");
    }
}
