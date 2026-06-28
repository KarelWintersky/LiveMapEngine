<?php

namespace App\Units;

interface MapConfigDriverInterface
{
    /**
     * @param  string $map_id
     * @return array  raw config as associative array
     * @throws \Throwable
     */
    public function load(string $map_id): array;

    public function getDriverName(): string;
}
