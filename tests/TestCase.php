<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Tests;

use Amon\ModuleGenerator\ModuleGeneratorServiceProvider;
use Inertia\ServiceProvider as InertiaServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [InertiaServiceProvider::class, ModuleGeneratorServiceProvider::class];
    }

    /** Redirige le projet hôte vers un bac à sable « installé » (aucune écriture dans le squelette Testbench). */
    protected function useSandboxProject(): string
    {
        $base = installedSandbox();
        $this->app->setBasePath($base);
        config([
            'module-generator.manifest_path' => $base.'/.module-generator',
            'module-generator.format.pint' => false,
        ]);

        return $base;
    }
}
