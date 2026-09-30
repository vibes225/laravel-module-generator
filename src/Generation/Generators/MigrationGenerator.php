<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Generation\Generators;

use Amon\ModuleGenerator\Contracts\Generator;
use Amon\ModuleGenerator\Definition\KeyType;
use Amon\ModuleGenerator\Definition\MorphOptions;
use Amon\ModuleGenerator\Definition\PivotMode;
use Amon\ModuleGenerator\Definition\PivotSide;
use Amon\ModuleGenerator\Definition\RelationDefinition;
use Amon\ModuleGenerator\Definition\RelationType;
use Amon\ModuleGenerator\Fields\Types\ForeignIdType;
use Amon\ModuleGenerator\Generation\Code;
use Amon\ModuleGenerator\Generation\GenerationContext;
use Amon\ModuleGenerator\Generation\PlannedFile;
use Amon\ModuleGenerator\Support\Escaper;

/** Migration de la table du module, puis une migration par table pivot générée (belongsToMany). */
final class MigrationGenerator implements Generator
{
    public function applies(GenerationContext $context): bool
    {
        return true;
    }

    public function generate(GenerationContext $context): array
    {
        $now = $context->settings->now;
        $files = [$this->migration($context, $context->names->table, $this->columns($context), $now->format('Y_m_d_His'), $context->definition->tree)];
        $offset = 1;

        foreach ($context->definition->relations as $relation) {
            if ($relation->pivot?->mode === PivotMode::Generate) {
                $timestamp = $now->modify("+{$offset} second")->format('Y_m_d_His');
                $files[] = $this->migration($context, (string) $relation->pivot->table, $this->pivotColumns($context, $relation), $timestamp, false);
                $offset++;
            }
        }

        return $files;
    }

    /** @param  list<string>  $columns */
    private function migration(GenerationContext $context, string $table, array $columns, string $timestamp, bool $nestedSet): PlannedFile
    {
        return new PlannedFile(
            "database/migrations/{$timestamp}_create_{$table}_table.php",
            $context->render('migration', [
                'IMPORTS' => $nestedSet ? "\nuse Kalnoy\Nestedset\NestedSet;" : '',
                'TABLE' => $table,
                'COLUMNS' => Code::indent(implode("\n", array_map(fn (string $column) => $column.';', $columns)), 3),
            ]),
            'migration',
            "Migration de la table {$table}",
        );
    }

    /** @return list<string> */
    private function columns(GenerationContext $context): array
    {
        $definition = $context->definition;
        $pivot = $definition->pivot;
        $columns = [];

        if ($pivot === null || $pivot->primaryId) {
            $columns[] = '$table->id()';
        }

        $pairColumns = [];

        if ($pivot !== null) {
            foreach ([$pivot->left, $pivot->right] as $side) {
                if ($side->polymorphic) {
                    $morph = $definition->morph;
                    $columns[] = $this->morphs($morph, false);
                    array_push($pairColumns, $morph->name.'_type', $morph->name.'_id');
                } else {
                    $columns[] = $this->sideColumn($side);
                    $pairColumns[] = (string) $side->foreignKey;
                }
            }
        } elseif ($definition->morph !== null) {
            $columns[] = $this->morphs($definition->morph, $definition->morph->nullable);
        }

        if ($definition->tree) {
            $columns[] = 'NestedSet::columns($table)';
        }

        foreach ($definition->fields as $field) {
            $columns[] = $context->type($field)->migrationColumn($field);
        }

        foreach ($definition->relations as $relation) {
            if ($relation->type === RelationType::BelongsTo) {
                $columns[] = $this->belongsToColumn($relation);
            }
        }

        if ($definition->options->timestamps) {
            $columns[] = '$table->timestamps()';
        }

        if ($definition->options->softDeletes) {
            $columns[] = '$table->softDeletes()';
        }

        if ($pivot !== null && $pivot->uniquePair) {
            $columns[] = '$table->'.($pivot->primaryId ? 'unique' : 'primary').'(['.Code::stringList($pairColumns).'])';
        }

        return $columns;
    }

    /** @return list<string> */
    private function pivotColumns(GenerationContext $context, RelationDefinition $relation): array
    {
        $foreign = (string) $relation->foreignPivotKey;
        $related = (string) $relation->relatedPivotKey;
        $columns = [
            '$table->foreignId('.Escaper::php($foreign).')->constrained('.Escaper::php($context->names->table).')->cascadeOnDelete()',
            '$table->foreignId('.Escaper::php($related).')->constrained('.Escaper::php((string) $relation->targetTable).')->cascadeOnDelete()',
        ];

        foreach ($relation->pivot->fields ?? [] as $field) {
            $columns[] = $context->type($field)->migrationColumn($field);
        }

        if ($relation->pivot?->timestamps) {
            $columns[] = '$table->timestamps()';
        }

        $columns[] = '$table->primary(['.Code::stringList([$foreign, $related]).'])';

        return $columns;
    }

    private function belongsToColumn(RelationDefinition $relation): string
    {
        return '$table->foreignId('.Escaper::php((string) $relation->foreignKey).')'
            .($relation->nullable ? '->nullable()' : '')
            .'->constrained('.Escaper::php((string) $relation->targetTable).')'
            .ForeignIdType::onDelete((string) $relation->onDelete);
    }

    private function sideColumn(PivotSide $side): string
    {
        return '$table->foreignId('.Escaper::php((string) $side->foreignKey).')->constrained('.Escaper::php((string) $side->table).')'
            .ForeignIdType::onDelete($side->onDelete);
    }

    private function morphs(MorphOptions $morph, bool $nullable): string
    {
        $method = match ($morph->keyType) {
            KeyType::Uuid => 'uuidMorphs',
            KeyType::Ulid => 'ulidMorphs',
            KeyType::Integer => 'morphs',
        };

        return '$table->'.($nullable ? 'nullable'.ucfirst($method) : $method).'('.Escaper::php($morph->name).')';
    }
}
