<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Generation\Generators;

use Amon\ModuleGenerator\Definition\RelationType;
use Amon\ModuleGenerator\Generation\Facts;
use Amon\ModuleGenerator\Generation\GenerationContext;
use Amon\ModuleGenerator\Support\Escaper;
use Illuminate\Support\Str;

/** Colonnes du tableau, filtres de la liste et lignes de la fiche, à partir de la définition. */
final class ColumnBuilder
{
    /** @var list<string> fonctions et composants du kit utilisés */
    private array $imports = [];

    public function __construct(private readonly GenerationContext $context) {}

    /** @return list<string> objets colonnes JSX (DataTable) */
    public function columns(): array
    {
        $definition = $this->context->definition;
        $columns = [];

        foreach ($this->sides() as [$key, $label, $relation, $display]) {
            $columns[] = $this->column($key, $label, false, "row.{$relation}?.{$display} ?? '—'");
        }

        if ($definition->morph !== null) {
            array_push($columns, ...$this->morphColumns('row'));
        }

        $title = Facts::titleField($definition);

        foreach ($definition->fields as $field) {
            if (! $field->inTable) {
                continue;
            }

            $type = $this->context->type($field);
            $expression = $type->display($field, 'row');
            array_push($this->imports, ...$type->displayImports($field));

            if ($definition->tree && $field->name === $title) {
                $expression = "<span style={{ paddingLeft: indent(row) }}>{{$expression}}</span>";
            }

            $columns[] = $this->column($field->name, $field->label, $field->sortable, $expression);
        }

        foreach (Facts::relations($definition, RelationType::BelongsTo) as $relation) {
            if ($relation->inTable) {
                $columns[] = $this->column((string) $relation->foreignKey, $relation->label, false, 'row.'.Str::snake($relation->name)."?.{$relation->display} ?? '—'");
            }
        }

        return $columns;
    }

    /** @return list<string> filtres (Filters) */
    public function filters(): array
    {
        $definition = $this->context->definition;
        $filters = [];

        foreach ($this->sides() as [$key, $label]) {
            $filters[] = $this->filter($key, $label);
        }

        if (Facts::morphInForm($definition)) {
            $filters[] = $this->filter($definition->morph->name.'_type', 'Type');
        }

        foreach ($definition->fields as $field) {
            if ($field->filterable) {
                $filters[] = $this->filter($field->name, $field->label);
            }
        }

        foreach (Facts::relations($definition, RelationType::BelongsTo) as $relation) {
            if ($relation->inTable) {
                $filters[] = $this->filter((string) $relation->foreignKey, $relation->label);
            }
        }

        return $filters;
    }

    /** @return list<array{string, string}> [libellé, expression] des lignes de la fiche */
    public function details(): array
    {
        $definition = $this->context->definition;
        $details = [];

        foreach ($this->sides() as [, $label, $relation, $display]) {
            $details[] = [$label, "record.{$relation}?.{$display} ?? '—'"];
        }

        if ($definition->morph !== null) {
            foreach ($this->morphParts('record') as [$label, $expression]) {
                $details[] = [$label, $expression];
            }
        }

        if ($definition->tree) {
            $details[] = ['Parent', 'record.parent?.'.Facts::titleField($definition)." ?? '—'"];
        }

        foreach ($definition->fields as $field) {
            if ($field->inDetail) {
                $type = $this->context->type($field);
                $details[] = [$field->label, $type->display($field, 'record')];
                array_push($this->imports, ...$type->displayImports($field));
            }
        }

        foreach ($definition->relations as $relation) {
            $key = Str::snake($relation->name);

            if ($relation->type === RelationType::BelongsTo) {
                $details[] = [$relation->label, "record.{$key}?.{$relation->display} ?? '—'"];
            } elseif ($relation->type === RelationType::BelongsToMany) {
                $details[] = [$relation->label, "record.{$key}?.map((item) => item.{$relation->display}).join(', ') || '—'"];
            }
        }

        if ($definition->options->timestamps) {
            $this->imports[] = 'formatDateTime';
            $details[] = ['Créé le', 'formatDateTime(record.created_at)'];
            $details[] = ['Modifié le', 'formatDateTime(record.updated_at)'];
        }

        return $details;
    }

    /** @return list<string> */
    public function imports(): array
    {
        return array_values(array_unique($this->imports));
    }

    /** @return list<array{string, string, string, string}> [clé, libellé, relation sérialisée, colonne affichée] */
    private function sides(): array
    {
        $pivot = $this->context->definition->pivot;
        $sides = [];

        foreach ($pivot === null ? [] : [$pivot->left, $pivot->right] as $side) {
            if (! $side->polymorphic) {
                $sides[] = [(string) $side->foreignKey, (string) $side->model, Str::snake(Facts::sideMethod($side)), $side->display];
            }
        }

        return $sides;
    }

    /** @return list<string> */
    private function morphColumns(string $variable): array
    {
        $columns = [];
        $name = $this->context->definition->morph->name;

        foreach ($this->morphParts($variable) as $index => [$label, $expression]) {
            $columns[] = $this->column($name.($index === 0 ? '_type' : '_id'), $label, false, $expression);
        }

        return $columns;
    }

    /** @return list<array{string, string}> type puis élément */
    private function morphParts(string $variable): array
    {
        $morph = $this->context->definition->morph;
        $name = $morph->name;
        $relation = Str::snake(Str::camel($name));

        if ($morph->types === []) {
            return [['Type', "{$variable}.{$name}_type ?? '—'"], ['Élément', "{$variable}.{$name}_id ?? '—'"]];
        }

        $this->imports[] = 'optionLabel';

        return [
            ['Type', "optionLabel(options.{$name}_type, {$variable}.{$name}_type)"],
            ['Élément', "{$variable}.{$relation}?.[options.{$name}_display?.[{$variable}.{$name}_type]] ?? {$variable}.{$name}_id ?? '—'"],
        ];
    }

    private function column(string $key, string $label, bool $sortable, string $render): string
    {
        $lines = ['{', "    key: '{$key}',", '    label: '.Escaper::js($label).','];

        if ($sortable) {
            $lines[] = '    sortable: true,';
        }

        $lines[] = "    render: (row) => {$render},";
        $lines[] = '},';

        return implode("\n", $lines);
    }

    private function filter(string $name, string $label): string
    {
        return "{ name: '{$name}', label: ".Escaper::js($label).", options: options.{$name} ?? [] },";
    }
}
