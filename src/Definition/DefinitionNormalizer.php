<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Definition;

use Amon\ModuleGenerator\Fields\FieldTypeRegistry;
use Amon\ModuleGenerator\Naming\Inflector;
use Illuminate\Support\Str;

/**
 * Complète une entrée brute (UI, CLI, manifest) : valeurs par défaut et noms dérivés (table, slug, libellés...).
 * Ne valide rien et ne plante jamais : les valeurs invalides sont laissées telles quelles pour le validateur.
 * Idempotent : normaliser une définition déjà normalisée ne change rien.
 */
final class DefinitionNormalizer
{
    private const PRESENTATION = ['searchable', 'sortable', 'filterable', 'in_table', 'in_form', 'in_detail'];

    public function __construct(
        private readonly FieldTypeRegistry $types,
        private readonly Inflector $inflector,
        private readonly string $modelsNamespace = 'App\Models',
    ) {}

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function normalize(array $input): array
    {
        $model = $this->deriveModel($input);
        $singular = $input['singular'] ?? (is_string($model) ? $this->headline($model) : null);
        $kind = $input['kind'] ?? 'standard';
        $pivot = $kind === 'pivot' ? $this->pivotModule($input['pivot'] ?? []) : ($input['pivot'] ?? null);
        $table = $input['table']
            ?? ($kind === 'pivot' ? $this->pivotModuleTable($pivot) : null)
            ?? (is_string($model) ? $this->inflector->table($model) : null);
        $fields = array_map(fn ($field) => $this->field($field), $this->list($input['fields'] ?? []));

        $normalized = [
            'schema_version' => $input['schema_version'] ?? ModuleDefinition::SCHEMA_VERSION,
            'kind' => $kind,
            'name' => $input['name'] ?? (is_string($singular) ? $this->pluralLabel($singular) : null),
            'singular' => $singular,
            'model' => $model,
            'table' => $table,
            'slug' => $input['slug'] ?? (is_string($table) ? str_replace('_', '-', $table) : null),
            'icon' => $input['icon'] ?? 'folder',
            'menu' => $this->menu($input['menu'] ?? []),
            'options' => $this->options($input['options'] ?? [], $fields, ($input['tree'] ?? false) === true),
            'fields' => $fields,
            'relations' => array_map(fn ($relation) => $this->relation($relation, $model), $this->list($input['relations'] ?? [])),
            'tree' => $input['tree'] ?? false,
            'morph' => $this->morph($input['morph'] ?? null),
            'pivot' => $pivot,
        ];

        return $normalized + array_diff_key($input, $normalized);
    }

    /** @param  array<string, mixed>  $input */
    private function deriveModel(array $input): mixed
    {
        if (isset($input['model'])) {
            return $input['model'];
        }

        return isset($input['singular']) && is_string($input['singular'])
            ? Str::studly(Str::ascii($input['singular']))
            : null;
    }

    private function headline(string $identifier): string
    {
        return ucfirst(str_replace('_', ' ', Str::snake($identifier)));
    }

    private function pluralLabel(string $singular): string
    {
        $words = explode(' ', $singular);
        $last = array_pop($words);
        $plural = $this->inflector->plural(mb_strtolower($last));

        if ($last !== '' && mb_strtoupper(mb_substr($last, 0, 1)) === mb_substr($last, 0, 1)) {
            $plural = mb_strtoupper(mb_substr($plural, 0, 1)).mb_substr($plural, 1);
        }

        return implode(' ', [...$words, $plural]);
    }

    /** @return list<mixed> */
    private function list(mixed $value): array
    {
        return is_array($value) ? array_values($value) : [];
    }

    /** @return array<string, mixed> */
    private function menu(mixed $menu): array
    {
        $menu = is_array($menu) ? $menu : [];
        $normalized = [
            'enabled' => $menu['enabled'] ?? true,
            'group' => $menu['group'] ?? 'main',
            'order' => $menu['order'] ?? 100,
            'permission' => $menu['permission'] ?? null,
        ];

        return $normalized + array_diff_key($menu, $normalized);
    }

    /**
     * @param  list<mixed>  $fields
     * @return array<string, mixed>
     */
    private function options(mixed $options, array $fields, bool $tree): array
    {
        $options = is_array($options) ? $options : [];
        $sort = is_array($options['default_sort'] ?? null) ? $options['default_sort'] : [];
        $normalized = [
            'timestamps' => $options['timestamps'] ?? true,
            'soft_deletes' => $options['soft_deletes'] ?? false,
            'per_page' => $options['per_page'] ?? 15,
            'default_sort' => [
                'field' => $sort['field'] ?? ($tree ? '_lft' : $this->firstSortable($fields)),
                'direction' => $sort['direction'] ?? 'asc',
            ] + array_diff_key($sort, ['field' => 1, 'direction' => 1]),
        ];

        return $normalized + array_diff_key($options, $normalized);
    }

    /** @param  list<mixed>  $fields */
    private function firstSortable(array $fields): string
    {
        foreach ($fields as $field) {
            if (is_array($field) && ($field['sortable'] ?? false) === true && ($field['in_table'] ?? false) === true) {
                return (string) $field['name'];
            }
        }

        return 'id';
    }

    /** Retourne le tableau normalisé, ou la valeur brute si ce n'est pas un tableau. */
    private function field(mixed $field, bool $pivot = false): mixed
    {
        if (! is_array($field)) {
            return $field;
        }

        $type = is_string($field['type'] ?? null) && $this->types->has($field['type']) ? $this->types->get($field['type']) : null;
        $options = is_array($field['options'] ?? null) ? $field['options'] : [];
        $presentation = $type?->presentationDefaults() ?? array_fill_keys(self::PRESENTATION, false);

        $normalized = [
            'name' => $field['name'] ?? null,
            'label' => $field['label'] ?? (is_string($field['name'] ?? null) ? $this->headline($field['name']) : null),
            'type' => $field['type'] ?? null,
            'nullable' => $field['nullable'] ?? false,
            'unique' => $field['unique'] ?? false,
            'default' => $field['default'] ?? null,
            'index' => $field['index'] ?? false,
            'options' => $type ? $type->normalizeOptions($options) : ($field['options'] ?? []),
        ];

        // Les champs d'une table pivot n'ont pas de drapeaux de présentation.
        foreach (self::PRESENTATION as $flag) {
            $normalized[$flag] = $pivot ? false : ($field[$flag] ?? $presentation[$flag]);
        }

        return $normalized + array_diff_key($field, $normalized);
    }

    private function relation(mixed $relation, mixed $model): mixed
    {
        if (! is_array($relation)) {
            return $relation;
        }

        $type = $relation['type'] ?? null;
        $target = $relation['target'] ?? null;
        $targetSnake = is_string($target) && $target !== '' ? Str::snake($target) : null;
        $own = is_string($model) && $model !== '' ? Str::snake($model) : null;
        $many = in_array($type, ['hasMany', 'belongsToMany'], true);

        $name = $relation['name'] ?? ($targetSnake === null ? null
            : Str::camel($many ? $this->inflector->pluralizeSnake($targetSnake) : $targetSnake));
        $nullable = $relation['nullable'] ?? false;

        $normalized = [
            'type' => $type,
            'name' => $name,
            'label' => $relation['label'] ?? (is_string($name) ? $this->headline($name) : null),
            'target' => $target,
            'target_table' => $relation['target_table'] ?? ($targetSnake === null ? null : $this->inflector->pluralizeSnake($targetSnake)),
            'foreign_key' => $relation['foreign_key'] ?? match ($type) {
                'belongsTo' => is_string($name) ? Str::snake($name).'_id' : null,
                'hasOne', 'hasMany' => $own === null ? null : $own.'_id',
                default => null,
            },
            'nullable' => $nullable,
            'on_delete' => $relation['on_delete'] ?? ($type === 'belongsTo' ? ($nullable === true ? 'null' : 'restrict') : null),
            'display' => $relation['display'] ?? (in_array($type, ['belongsTo', 'belongsToMany'], true) ? 'name' : null),
            'in_table' => $relation['in_table'] ?? ($type === 'belongsTo'),
            'in_form' => $relation['in_form'] ?? ($type === 'belongsTo'),
            'foreign_pivot_key' => $relation['foreign_pivot_key'] ?? ($type === 'belongsToMany' && $own !== null ? $own.'_id' : null),
            'related_pivot_key' => $relation['related_pivot_key'] ?? ($type === 'belongsToMany' && $targetSnake !== null ? $targetSnake.'_id' : null),
            'pivot' => $type === 'belongsToMany'
                ? $this->pivotSpec($relation['pivot'] ?? [], $own, $targetSnake)
                : ($relation['pivot'] ?? null),
        ];

        return $normalized + array_diff_key($relation, $normalized);
    }

    private function pivotSpec(mixed $pivot, ?string $own, ?string $target): mixed
    {
        if (! is_array($pivot)) {
            return $pivot;
        }

        $mode = $pivot['mode'] ?? 'none';
        $normalized = [
            'mode' => $mode,
            'table' => $pivot['table'] ?? ($mode === 'generate' ? $this->pivotTable($own, $target) : null),
            'timestamps' => $pivot['timestamps'] ?? false,
            'fields' => array_map(fn ($field) => $this->field($field, pivot: true), $this->list($pivot['fields'] ?? [])),
            'module' => $pivot['module'] ?? null,
            'model' => $pivot['model'] ?? null,
        ];

        return $normalized + array_diff_key($pivot, $normalized);
    }

    /** Convention Laravel : les deux noms au singulier, en ordre alphabétique (`client_tag`). */
    private function pivotTable(?string $a, ?string $b): ?string
    {
        if ($a === null || $b === null) {
            return null;
        }

        $names = [$a, $b];
        sort($names);

        return implode('_', $names);
    }

    private function morph(mixed $morph): mixed
    {
        if (! is_array($morph)) {
            return $morph;
        }

        $normalized = [
            'name' => $morph['name'] ?? null,
            'key_type' => $morph['key_type'] ?? 'int',
            'nullable' => $morph['nullable'] ?? false,
            'types' => array_map(fn ($type) => $this->morphType($type), $this->list($morph['types'] ?? [])),
        ];

        return $normalized + array_diff_key($morph, $normalized);
    }

    private function morphType(mixed $type): mixed
    {
        if (! is_array($type)) {
            return $type;
        }

        $model = $type['model'] ?? null;
        $valid = is_string($model) && $model !== '';
        $normalized = [
            'model' => $model,
            'class' => $type['class'] ?? ($valid ? $this->modelsNamespace.chr(92).$model : null),
            'label' => $type['label'] ?? ($valid ? $this->headline($model) : null),
            'display_column' => $type['display_column'] ?? 'name',
        ];

        return $normalized + array_diff_key($type, $normalized);
    }

    /** @return array<string, mixed> */
    private function pivotModule(mixed $pivot): array
    {
        $pivot = is_array($pivot) ? $pivot : [];
        $normalized = [
            'left' => $this->pivotSide($pivot['left'] ?? []),
            'right' => $this->pivotSide($pivot['right'] ?? []),
            'unique_pair' => $pivot['unique_pair'] ?? true,
            'primary_id' => $pivot['primary_id'] ?? true,
        ];

        return $normalized + array_diff_key($pivot, $normalized);
    }

    private function pivotSide(mixed $side): mixed
    {
        if (! is_array($side)) {
            return $side;
        }

        $polymorphic = $side['polymorphic'] ?? false;
        $model = $side['model'] ?? null;
        $derive = $polymorphic !== true && is_string($model) && $model !== '';
        $normalized = [
            'model' => $model,
            'table' => $side['table'] ?? ($derive ? $this->inflector->table($model) : null),
            'foreign_key' => $side['foreign_key'] ?? ($derive ? Str::snake($model).'_id' : null),
            'on_delete' => $side['on_delete'] ?? 'cascade',
            'polymorphic' => $polymorphic,
            'display' => $side['display'] ?? 'name',
        ];

        return $normalized + array_diff_key($side, $normalized);
    }

    /** @param  array<string, mixed>  $pivot */
    private function pivotModuleTable(array $pivot): ?string
    {
        $model = fn ($side) => is_array($side) && is_string($side['model'] ?? null) && $side['model'] !== ''
            ? Str::snake($side['model'])
            : null;

        return $this->pivotTable($model($pivot['left']), $model($pivot['right']));
    }
}
