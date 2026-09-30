<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator;

use Amon\ModuleGenerator\Definition\DefinitionNormalizer;
use Amon\ModuleGenerator\Fields\FieldTypeRegistry;
use Amon\ModuleGenerator\Naming\Inflector;
use Illuminate\Support\ServiceProvider;

class ModuleGeneratorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/module-generator.php', 'module-generator');

        $this->app->singleton(FieldTypeRegistry::class, fn ($app) => FieldTypeRegistry::fromClasses(
            $app['config']->get('module-generator.field_types', [])
        ));

        $this->app->singleton(Inflector::class, fn ($app) => new Inflector(
            (bool) $app['config']->get('module-generator.naming.french_plural', false)
        ));

        $this->app->singleton(DefinitionNormalizer::class, fn ($app) => new DefinitionNormalizer(
            $app->make(FieldTypeRegistry::class),
            $app->make(Inflector::class),
            (string) $app['config']->get('module-generator.naming.models_namespace', 'App\Models'),
        ));
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
