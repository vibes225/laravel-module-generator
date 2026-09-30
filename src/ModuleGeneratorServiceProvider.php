<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator;

use Amon\ModuleGenerator\Console\InstallCommand;
use Amon\ModuleGenerator\Console\ListCommand;
use Amon\ModuleGenerator\Console\MakeCommand;
use Amon\ModuleGenerator\Console\PublishStubsCommand;
use Amon\ModuleGenerator\Console\RemoveCommand;
use Amon\ModuleGenerator\Definition\DefinitionFactory;
use Amon\ModuleGenerator\Definition\DefinitionNormalizer;
use Amon\ModuleGenerator\Fields\FieldTypeRegistry;
use Amon\ModuleGenerator\Generation\DefaultGenerators;
use Amon\ModuleGenerator\Generation\Formatting\ProcessFormatter;
use Amon\ModuleGenerator\Generation\GenerationSettings;
use Amon\ModuleGenerator\Generation\ModuleGenerator;
use Amon\ModuleGenerator\Generation\PlanExecutor;
use Amon\ModuleGenerator\Generation\PlanInspector;
use Amon\ModuleGenerator\Http\Middleware\HandleToolRequests;
use Amon\ModuleGenerator\Manifest\ManifestRepository;
use Amon\ModuleGenerator\Naming\Inflector;
use Amon\ModuleGenerator\Registry\ModuleRegistry;
use Amon\ModuleGenerator\Removal\Remover;
use Amon\ModuleGenerator\Stubs\StubRenderer;
use Amon\ModuleGenerator\Stubs\StubResolver;
use Amon\ModuleGenerator\Support\LaravelVersion;
use Amon\ModuleGenerator\Support\PackageVersion;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ModuleGeneratorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/module-generator.php', 'module-generator');

        $this->app->singleton(FieldTypeRegistry::class, fn (Application $app) => FieldTypeRegistry::fromClasses(
            $app['config']->get('module-generator.field_types', [])
        ));

        $this->app->singleton(Inflector::class, fn (Application $app) => new Inflector(
            (bool) $app['config']->get('module-generator.naming.french_plural', false)
        ));

        $this->app->singleton(DefinitionNormalizer::class, fn (Application $app) => new DefinitionNormalizer(
            $app->make(FieldTypeRegistry::class),
            $app->make(Inflector::class),
            (string) $app['config']->get('module-generator.naming.models_namespace', 'App\Models'),
        ));

        $this->app->singleton(ManifestRepository::class, fn (Application $app) => new ManifestRepository(
            rtrim((string) $app['config']->get('module-generator.manifest_path', base_path('.module-generator')), '/')
        ));

        $this->app->singleton(ModuleRegistry::class);

        $this->app->singleton(StubRenderer::class, fn (Application $app) => new StubRenderer(new StubResolver(
            dirname(__DIR__).'/stubs',
            $app['config']->get('module-generator.stubs_path'),
            LaravelVersion::major($app->version()),
        )));

        $this->app->singleton(ModuleGenerator::class, fn (Application $app) => new ModuleGenerator(
            DefaultGenerators::all(),
            $app->make(FieldTypeRegistry::class),
            $app->make(StubRenderer::class),
        ));

        $this->app->singleton(PlanInspector::class, fn (Application $app) => new PlanInspector(
            base_path(),
            $app->make(ModuleRegistry::class),
        ));

        $this->app->singleton(PlanExecutor::class, fn (Application $app) => new PlanExecutor(
            base_path(),
            $app->make(ManifestRepository::class),
            new ProcessFormatter(
                (bool) $app['config']->get('module-generator.format.pint', true),
                (bool) $app['config']->get('module-generator.format.prettier', false),
            ),
            ['generator' => PackageVersion::get(), 'laravel' => $app->version(), 'php' => PHP_VERSION],
        ));

        $this->app->singleton(ModuleService::class, fn (Application $app) => new ModuleService(
            $app->make(DefinitionNormalizer::class),
            $app->make(DefinitionFactory::class),
            $app->make(ModuleGenerator::class),
            $app->make(ModuleRegistry::class),
            $app->make(ManifestRepository::class),
            $app->make(PlanInspector::class),
            $app->make(PlanExecutor::class),
            fn () => new GenerationSettings(
                (string) $app['config']->get('module-generator.convention', 'breeze'),
                LaravelVersion::major($app->version()),
                (string) $app['config']->get('module-generator.naming.models_namespace', 'App\Models'),
                Date::now()->toDateTimeImmutable(),
                $app['config']->get('module-generator.pages_path'),
            ),
            new Remover(base_path(), $app->make(ManifestRepository::class)),
        ));
    }

    public function boot(): void
    {
        $this->loadViewsFrom(dirname(__DIR__).'/resources/views', 'module-generator');

        if (config('module-generator.ui.enabled')) {
            Route::middleware([...(array) config('module-generator.ui.middleware', ['web']), HandleToolRequests::class])
                ->prefix((string) config('module-generator.ui.prefix', 'module-generator'))
                ->name('module-generator.')
                ->group(dirname(__DIR__).'/routes/tool.php');
        }

        if ($this->app->runningInConsole()) {
            $this->registerPublishing();
            $this->commands([
                InstallCommand::class,
                MakeCommand::class,
                RemoveCommand::class,
                ListCommand::class,
                PublishStubsCommand::class,
            ]);
        }
    }

    private function registerPublishing(): void
    {
        $root = dirname(__DIR__);

        $this->publishes([
            $root.'/config/module-generator.php' => config_path('module-generator.php'),
        ], 'module-generator-config');

        $this->publishes([
            $root.'/stubs' => (string) config('module-generator.stubs_path', base_path('stubs/module-generator')),
        ], 'module-generator-stubs');

        $this->publishes([
            $root.'/scaffold' => base_path(),
        ], 'module-generator-scaffold');

        $this->publishes([
            $root.'/dist' => public_path('vendor/module-generator'),
        ], 'module-generator-assets');
    }
}
