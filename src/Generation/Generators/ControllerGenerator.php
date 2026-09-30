<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Generation\Generators;

use Amon\ModuleGenerator\Contracts\Generator;
use Amon\ModuleGenerator\Definition\FieldDefinition;
use Amon\ModuleGenerator\Definition\RelationType;
use Amon\ModuleGenerator\Fields\FileLike;
use Amon\ModuleGenerator\Generation\Code;
use Amon\ModuleGenerator\Generation\Facts;
use Amon\ModuleGenerator\Generation\GenerationContext;
use Amon\ModuleGenerator\Generation\PlannedFile;
use Amon\ModuleGenerator\Support\Escaper;
use Illuminate\Support\Str;

final class ControllerGenerator implements Generator
{
    /** @var list<string> */
    private array $imports = [];

    public function applies(GenerationContext $context): bool
    {
        return true;
    }

    public function generate(GenerationContext $context): array
    {
        $names = $context->names;
        $definition = $context->definition;
        $this->imports = [
            'App\Http\Controllers\Controller',
            'App\Filters'.chr(92).$names->filter,
            'App\Http\Requests\Admin'.chr(92).$names->storeRequest,
            $names->modelClass,
            'Illuminate\Http\RedirectResponse',
            'Illuminate\Http\Request',
            'Inertia\Inertia',
            'Inertia\Response',
        ];
        $variable = '$'.$names->variable;

        $values = [
            'CLASS' => $names->controller,
            'FILTER' => $names->filter,
            'MODEL' => $names->model,
            'VARIABLE' => $names->variable,
            'ROUTE' => $names->routeName,
            'PAGE_INDEX' => $names->page('Index'),
            'PAGE_CREATE' => $names->page('Create'),
            'STORE_REQUEST' => $names->storeRequest,
            'INDEX_QUERY' => $this->indexQuery($context),
            'OPTIONS' => $this->options($context),
        ];

        if (Facts::isPair($definition)) {
            $pivot = $definition->pivot;
            $values += [
                'LEFT_PARAMETER' => (string) $pivot->left->foreignKey,
                'RIGHT_PARAMETER' => (string) $pivot->right->foreignKey,
                'LEFT_KEY' => (string) $pivot->left->foreignKey,
                'RIGHT_KEY' => (string) $pivot->right->foreignKey,
            ];
            $stub = 'controller-pair';
        } else {
            $this->imports[] = 'App\Http\Requests\Admin'.chr(92).$names->updateRequest;
            $synced = Facts::syncedRelations($definition);
            $values += [
                'PAGE_SHOW' => $names->page('Show'),
                'PAGE_EDIT' => $names->page('Edit'),
                'UPDATE_REQUEST' => $names->updateRequest,
                'SAVE_DATA' => $synced === [] ? '$data' : $this->import('Illuminate\Support\Arr').'::except($data, ['
                    .Code::stringList(array_map(fn ($relation) => $relation->name, $synced)).'])',
                'STORE_PREPARE' => $this->block($this->prepare($context, $variable, false), "\n", "\n\n"),
                'UPDATE_PREPARE' => $this->block($this->prepare($context, $variable, true), "\n", "\n\n"),
                'STORE_AFTER' => $this->block($this->sync($context, $variable), '', "\n"),
                'UPDATE_AFTER' => $this->block($this->sync($context, $variable), '', "\n"),
                'DESTROY_AFTER' => $this->block($this->destroyFiles($context, $variable), "\n", "\n"),
                'SHOW_RECORD' => $this->showRecord($context, $variable),
                'EDIT_RECORD' => $this->editRecord($context, $variable),
            ];
            $stub = 'controller';
        }

        $values['IMPORTS'] = Code::uses($this->imports, 'App\Http\Controllers\Admin');

        return [new PlannedFile(
            'app/Http/Controllers/Admin/'.$names->controller.'.php',
            $context->render($stub, $values),
            'controller',
            'Contrôleur '.$names->controller,
        )];
    }

    private function import(string $class): string
    {
        $this->imports[] = $class;

        return Code::basename($class);
    }

    /** Bloc d'instructions indenté dans une méthode (niveau 2), entouré des séparateurs donnés s'il n'est pas vide. */
    private function block(array $statements, string $before, string $after): string
    {
        return $statements === [] ? '' : $before.Code::indent(implode("\n\n", $statements), 2).$after;
    }

    private function indexQuery(GenerationContext $context): string
    {
        $with = $this->eagerLoads($context, forIndex: true);
        $query = $with === [] ? '' : '->with(['.Code::stringList($with).'])';

        return $context->definition->tree ? $query.'->withDepth()' : $query;
    }

    /** @return list<string> */
    private function eagerLoads(GenerationContext $context, bool $forIndex): array
    {
        $definition = $context->definition;
        $relations = [];

        if ($definition->pivot !== null) {
            foreach ([$definition->pivot->left, $definition->pivot->right] as $side) {
                if (! $side->polymorphic) {
                    $relations[] = Facts::sideMethod($side);
                }
            }
        }

        if ($definition->morph !== null) {
            $relations[] = Str::camel($definition->morph->name);
        }

        if ($definition->tree && ! $forIndex) {
            $relations[] = 'parent';
        }

        foreach ($definition->relations as $relation) {
            $shown = $forIndex ? $relation->inTable : in_array($relation->type, [RelationType::BelongsTo, RelationType::BelongsToMany], true);

            if ($shown && in_array($relation->type, [RelationType::BelongsTo, RelationType::BelongsToMany], true)) {
                $relations[] = $relation->name;
            }
        }

        return $relations;
    }

    /** @return list<string> préparation des données validées : fichiers, JSON, mot de passe */
    private function prepare(GenerationContext $context, string $variable, bool $update): array
    {
        $statements = [];
        $slug = Escaper::php($context->names->slug);

        foreach (Facts::formFields($context->definition) as $field) {
            $name = $field->name;
            $key = Escaper::php($name);
            $type = $context->type($field);

            if ($type->isFile()) {
                $disk = Escaper::php($this->disk($context, $field));
                $store = "    \$data[{$key}] = \$request->file({$key})->store({$slug}, {$disk});";

                if ($update) {
                    $this->import('Illuminate\Support\Facades\Storage');
                    $statements[] = "if (\$request->hasFile({$key})) {\n    if ({$variable}->{$name}) {\n        Storage::disk({$disk})->delete({$variable}->{$name});\n    }\n\n"
                        ."{$store}\n} else {\n    unset(\$data[{$key}]);\n}";
                } else {
                    $statements[] = "if (\$request->hasFile({$key})) {\n{$store}\n}";
                }
            } elseif ($field->type === 'json') {
                $statements[] = "\$data[{$key}] = isset(\$data[{$key}]) ? json_decode(\$data[{$key}], true) : null;";
            } elseif ($field->type === 'password' && $update) {
                $statements[] = "if (blank(\$data[{$key}] ?? null)) {\n    unset(\$data[{$key}]);\n}";
            }
        }

        return $statements;
    }

    /** @return list<string> */
    private function sync(GenerationContext $context, string $variable): array
    {
        $lines = array_map(
            fn ($relation) => "{$variable}->{$relation->name}()->sync(\$data[".Escaper::php($relation->name).'] ?? []);',
            Facts::syncedRelations($context->definition),
        );

        return $lines === [] ? [] : [implode("\n", $lines)];
    }

    /** @return list<string> suppression des fichiers stockés (sauf suppression logique) */
    private function destroyFiles(GenerationContext $context, string $variable): array
    {
        if ($context->definition->options->softDeletes) {
            return [];
        }

        $statements = [];

        foreach (Facts::fileFields($context->definition, $context->types) as $field) {
            $this->import('Illuminate\Support\Facades\Storage');
            $disk = Escaper::php($this->disk($context, $field));
            $statements[] = "if ({$variable}->{$field->name}) {\n    Storage::disk({$disk})->delete({$variable}->{$field->name});\n}";
        }

        return $statements;
    }

    private function showRecord(GenerationContext $context, string $variable): string
    {
        $load = $this->eagerLoads($context, forIndex: false);

        return $load === [] ? $variable : "{$variable}->load([".Code::stringList($load).'])';
    }

    private function editRecord(GenerationContext $context, string $variable): string
    {
        $synced = Facts::syncedRelations($context->definition);

        if ($synced === []) {
            return $variable;
        }

        $lines = ["...{$variable}->toArray(),"];

        foreach ($synced as $relation) {
            $lines[] = Escaper::php($relation->name)." => {$variable}->{$relation->name}()->pluck("
                .Escaper::php($relation->targetTable.'.id').'),';
        }

        return "[\n".Code::indent(implode("\n", $lines), 4)."\n            ]";
    }

    /** Tableau d'options renvoyé par la méthode privée options() du contrôleur. */
    private function options(GenerationContext $context): string
    {
        $definition = $context->definition;
        $names = $context->names;
        $options = [];

        if ($definition->pivot !== null) {
            foreach ([$definition->pivot->left, $definition->pivot->right] as $side) {
                if (! $side->polymorphic) {
                    $options[(string) $side->foreignKey] = $this->records($names->modelFqcn((string) $side->model), $side->display);
                }
            }
        }

        if (Facts::morphInForm($definition)) {
            $morph = $definition->morph;
            $constant = $this->import($names->modelClass).'::'.Facts::morphConstant($morph->name);
            $options[$morph->name.'_type'] = "collect({$constant})\n    ->map(fn (string \$label, string \$class) => ['value' => \$class, 'label' => \$label])\n    ->values()\n    ->all()";
            $items = [];
            $display = [];

            foreach ($morph->types as $type) {
                $class = $this->import($type->class).'::class';
                $items[] = "{$class} => ".$this->records($type->class, $type->displayColumn).',';
                $display[] = "{$class} => ".Escaper::php($type->displayColumn).',';
            }

            $options[$morph->name.'_id'] = "[\n".Code::indent(implode("\n", $items), 1)."\n]";
            $options[$morph->name.'_display'] = "[\n".Code::indent(implode("\n", $display), 1)."\n]";
        }

        if ($definition->tree) {
            $title = Facts::titleField($definition);
            $options['parent_id'] = "{$names->model}::query()->withDepth()->defaultOrder()->get()\n"
                ."    ->map(fn ({$names->model} \$record) => ['value' => \$record->id, 'label' => str_repeat('— ', \$record->depth).\$record->{$title}])\n    ->all()";
        }

        foreach ($definition->fields as $field) {
            $type = $context->type($field);
            $expression = $type->optionsExpression($field, $names);

            if ($expression !== null && ($field->type === 'enum' || $field->filterable)) {
                foreach ($type->imports($field, $names) as $class) {
                    if (str_starts_with($class, 'App\Enums')) {
                        $this->imports[] = $class;
                    }
                }

                $options[$field->name] = $expression;
            } elseif ($field->filterable) {
                $column = Escaper::php($field->name);
                $options[$field->name] = "{$names->model}::query()->whereNotNull({$column})->distinct()->orderBy({$column})->pluck({$column})\n"
                    ."    ->map(fn (\$value) => ['value' => \$value, 'label' => (string) \$value])\n    ->all()";
            }
        }

        foreach ($definition->relations as $relation) {
            $listed = $relation->type === RelationType::BelongsTo ? ($relation->inForm || $relation->inTable) : $relation->inForm;

            if ($listed && in_array($relation->type, [RelationType::BelongsTo, RelationType::BelongsToMany], true)) {
                $key = $relation->type === RelationType::BelongsTo ? (string) $relation->foreignKey : $relation->name;
                $options[$key] = $this->records($names->modelFqcn($relation->target), (string) $relation->display);
            }
        }

        return $options === [] ? '[]' : "[\n".Code::arrayLines($options, 3)."\n        ]";
    }

    /** Liste complète des enregistrements d'un modèle en options { value, label }. */
    private function records(string $class, string $display): string
    {
        $model = $this->import($class);
        $column = Escaper::php($display);

        return "{$model}::query()->orderBy({$column})->get()\n"
            ."    ->map(fn ({$model} \$record) => ['value' => \$record->getKey(), 'label' => \$record->{$display}])\n    ->all()";
    }

    private function disk(GenerationContext $context, FieldDefinition $field): string
    {
        $type = $context->type($field);

        return $type instanceof FileLike ? $type->disk($field) : 'public';
    }
}
