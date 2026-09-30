<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Generation\Generators;

use Amon\ModuleGenerator\Contracts\Generator;
use Amon\ModuleGenerator\Definition\RelationType;
use Amon\ModuleGenerator\Generation\Code;
use Amon\ModuleGenerator\Generation\Facts;
use Amon\ModuleGenerator\Generation\GenerationContext;
use Amon\ModuleGenerator\Generation\PlannedFile;

final class FactoryGenerator implements Generator
{
    public function applies(GenerationContext $context): bool
    {
        return true;
    }

    public function generate(GenerationContext $context): array
    {
        $definition = $context->definition;
        $names = $context->names;
        $imports = ['Illuminate\Database\Eloquent\Factories\Factory', $names->modelClass];
        $values = [];

        if ($definition->pivot !== null) {
            foreach ([$definition->pivot->left, $definition->pivot->right] as $side) {
                if (! $side->polymorphic) {
                    $imports[] = $names->modelFqcn((string) $side->model);
                    $values[(string) $side->foreignKey] = $side->model.'::factory()';
                }
            }
        }

        $morph = $definition->morph;

        if ($morph !== null && $morph->types !== []) {
            $first = $morph->types[0];
            $nullable = $morph->nullable && $definition->pivot === null;

            if (! $nullable) {
                $imports[] = $first->class;
            }

            $values[$morph->name.'_type'] = $nullable ? 'null' : Code::basename($first->class).'::class';
            $values[$morph->name.'_id'] = $nullable ? 'null' : Code::basename($first->class).'::factory()';
        }

        foreach ($definition->fields as $field) {
            $type = $context->type($field);
            $values[$field->name] = $type->factoryValue($field, $names);

            foreach ($type->imports($field, $names) as $class) {
                if (str_starts_with($class, 'App\Enums')) {
                    $imports[] = $class;
                }
            }
        }

        foreach (Facts::relations($definition, RelationType::BelongsTo) as $relation) {
            if ($relation->nullable) {
                $values[(string) $relation->foreignKey] = 'null';
            } else {
                $imports[] = $names->modelFqcn($relation->target);
                $values[(string) $relation->foreignKey] = $relation->target.'::factory()';
            }
        }

        return [new PlannedFile(
            'database/factories/'.$names->factory.'.php',
            $context->render('factory', [
                'IMPORTS' => Code::uses($imports, 'Database\Factories'),
                'MODEL' => $names->model,
                'CLASS' => $names->factory,
                'VALUES' => Code::arrayLines($values, 3),
            ]),
            'factory',
            'Factory '.$names->factory,
        )];
    }
}
