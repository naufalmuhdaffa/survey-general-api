<?php

declare(strict_types=1);

namespace App\Interfaces;

interface RoutesInterface
{
    public static function dispatch(string $path, string $method, array $segments): bool;
}