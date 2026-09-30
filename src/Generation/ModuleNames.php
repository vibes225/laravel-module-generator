<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Generation;

use Amon\ModuleGenerator\Definition\ModuleDefinition;
use Illuminate\Support\Str;

/** Tout le nommage d'un module, calculé une fois. Chemins relatifs à la racine du projet, séparateurs `/`. */
final readonly class ModuleNames
{
    public string $model;

    public string $variable;

    public string $modelNamespace;

    public string $modelClass;

    public string $table;

    public string $slug;

    public string $routeName;

    public string $routeParameter;

    public string $controller;

    public string $storeRequest;

    public string $updateRequest;

    public string $filter;

    public string $factory;

    public string $pagesDirectory;

    public string $componentPrefix;

    public string $formComponent;

    public function __construct(ModuleDefinition $definition, private GenerationSettings $settings)
    {
        $this->model = $definition->model;
        $this->variable = Str::camel($definition->model);
        $this->modelNamespace = $settings->modelsNamespace;
        $this->modelClass = $settings->modelsNamespace.chr(92).$definition->model;
        $this->table = $definition->table;
        $this->slug = $definition->slug;
        $this->routeName = 'admin.'.$definition->slug;
        $this->routeParameter = Str::snake($definition->model);
        $this->controller = $definition->model.'Controller';
        $this->storeRequest = 'Store'.$definition->model.'Request';
        $this->updateRequest = 'Update'.$definition->model.'Request';
        $this->filter = $definition->model.'Filter';
        $this->factory = $definition->model.'Factory';

        $studly = Str::studly(str_replace('-', '_', $definition->slug));

        if ($settings->isStarterKit()) {
            $this->pagesDirectory = $settings->pagesRoot().'/'.$definition->slug;
            $this->componentPrefix = $definition->slug;
            $this->formComponent = Str::kebab($definition->model).'-form';
        } else {
            $this->pagesDirectory = $settings->pagesRoot().'/'.$studly;
            $this->componentPrefix = $studly;
            $this->formComponent = $definition->model.'Form';
        }
    }

    /** Nom de page Inertia : `Invoices/Index` (breeze) ou `invoices/index` (starter kit). */
    public function page(string $page): string
    {
        return $this->componentPrefix.'/'.($this->settings->isStarterKit() ? strtolower($page) : $page);
    }

    public function pagePath(string $page): string
    {
        return $this->pagesDirectory.'/'.($this->settings->isStarterKit() ? strtolower($page) : $page).'.jsx';
    }

    public function formPath(): string
    {
        return $this->pagesDirectory.'/partials/'.$this->formComponent.'.jsx';
    }

    /** Import relatif du kit UI (`resources/js/components/admin`) depuis les pages ou le dossier partials. */
    public function kitImport(bool $fromPartials = false): string
    {
        $from = explode('/', $this->pagesDirectory.($fromPartials ? '/partials' : ''));
        $to = ['resources', 'js', 'components', 'admin'];

        while ($from !== [] && $to !== [] && $from[0] === $to[0]) {
            array_shift($from);
            array_shift($to);
        }

        return str_repeat('../', count($from)).implode('/', $to);
    }

    public function modelPath(): string
    {
        return self::classPath($this->modelClass);
    }

    public function route(string $action): string
    {
        return $this->routeName.'.'.$action;
    }

    /** Classe d'enum d'un champ : `InvoiceStatus`. */
    public function enumClass(string $field): string
    {
        return $this->model.Str::studly($field);
    }

    /** Classe d'un modèle cible (relations, types polymorphes). */
    public function modelFqcn(string $model): string
    {
        return $this->modelNamespace.chr(92).$model;
    }

    /** `App\Models\Client` => `app/Models/Client.php` (PSR-4 `App\` => `app/`). */
    public static function classPath(string $class): string
    {
        $path = str_replace(chr(92), '/', $class);

        return (str_starts_with($path, 'App/') ? 'app/'.substr($path, 4) : $path).'.php';
    }
}
