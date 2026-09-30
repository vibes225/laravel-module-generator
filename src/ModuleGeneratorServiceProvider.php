<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator;

use Illuminate\Support\ServiceProvider;

class ModuleGeneratorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/module-generator.php', 'module-generator');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->registerPublishing();
        }
    }

    private function registerPublishing(): void
    {
        $root = dirname(__DIR__);

        $this->publishes([
            $root.'/config/module-generator.php' => config_path('module-generator.php'),
        ], 'module-generator-config');

        $this->publishes([
            $root.'/stubs' => base_path('stubs/module-generator'),
        ], 'module-generator-stubs');

        $this->publishes([
            $root.'/scaffold' => base_path(),
        ], 'module-generator-scaffold');

        $this->publishes([
            $root.'/dist' => public_path('vendor/module-generator'),
        ], 'module-generator-assets');
    }
}
