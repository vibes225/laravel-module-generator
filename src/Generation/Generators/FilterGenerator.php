<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Generation\Generators;

use Amon\ModuleGenerator\Contracts\Generator;
use Amon\ModuleGenerator\Definition\RelationType;
use Amon\ModuleGenerator\Generation\Code;
use Amon\ModuleGenerator\Generation\Facts;
use Amon\ModuleGenerator\Generation\GenerationContext;
use Amon\ModuleGenerator\Generation\PlannedFile;
use Amon\ModuleGenerator\Support\Escaper;

/** Filtre de liste : étend `App\Filters\QueryFilter` (publié dans le projet à l'installation). */
final class FilterGenerator implements Generator
{
    public function applies(GenerationContext $context): bool
    {
        return true;
    }

    public function generate(GenerationContext $context): array
    {
        $definition = $context->definition;
        $searchable = [];
        $sortable = [];
        $filters = [];

        foreach ($definition->fields as $field) {
            if ($field->searchable) {
                $searchable[] = $field->name;
            }

            if ($field->sortable) {
                $sortable[] = $field->name;
            }

            if ($field->filterable) {
                $filters[$field->name] = Escaper::php($field->name);
            }
        }

        foreach (Facts::relations($definition, RelationType::BelongsTo) as $relation) {
            if ($relation->inTable) {
                $filters[(string) $relation->foreignKey] = Escaper::php((string) $relation->foreignKey);
            }
        }

        if ($definition->pivot !== null) {
            foreach ([$definition->pivot->left, $definition->pivot->right] as $side) {
                if (! $side->polymorphic) {
                    $filters[(string) $side->foreignKey] = Escaper::php((string) $side->foreignKey);
                }
            }
        }

        if ($definition->morph !== null) {
            $filters[$definition->morph->name.'_type'] = Escaper::php($definition->morph->name.'_type');
        }

        if ($definition->pivot === null || $definition->pivot->primaryId) {
            array_unshift($sortable, 'id');
        }

        if ($definition->options->timestamps) {
            $sortable[] = 'created_at';
        }

        $defaultSort = $definition->options->sortField;

        // Arbre : tri par bord gauche pour l'affichage indenté.
        if ($definition->tree) {
            $sortable[] = '_lft';
        }

        if (! in_array($defaultSort, $sortable, true)) {
            $sortable[] = $defaultSort;
        }

        $filterBlock = $filters === [] ? '[]' : "[\n".Code::arrayLines($filters, 2)."\n    ]";

        return [new PlannedFile(
            'app/Filters/'.$context->names->filter.'.php',
            $context->render('filter', [
                'CLASS' => $context->names->filter,
                'SEARCHABLE' => Code::listBlock($searchable, 1),
                'SORTABLE' => Code::listBlock(array_values(array_unique($sortable)), 1),
                'FILTERS' => $filterBlock,
                'DEFAULT_SORT' => Escaper::php($defaultSort),
                'DEFAULT_DIRECTION' => Escaper::php($definition->options->sortDirection),
                'PER_PAGE' => (string) $definition->options->perPage,
            ]),
            'filter',
            'Filtre '.$context->names->filter,
        )];
    }
}
