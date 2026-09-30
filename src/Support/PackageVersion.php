<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Support;

use Composer\InstalledVersions;

final class PackageVersion
{
    public const NAME = 'amon/laravel-module-generator';

    public static function get(): string
    {
        if (class_exists(InstalledVersions::class) && InstalledVersions::isInstalled(self::NAME)) {
            return InstalledVersions::getPrettyVersion(self::NAME) ?? 'dev';
        }

        return 'dev';
    }
}
