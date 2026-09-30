<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Support;

/** Point unique où les différences entre Laravel 12 et 13 sont décidées (variantes de stubs `.l12` / `.l13`). */
final class LaravelVersion
{
    public static function major(string $version): int
    {
        return (int) explode('.', ltrim($version, 'v'))[0];
    }
}
