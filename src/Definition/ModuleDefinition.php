<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Definition;

/**
 * Définition immuable d'un module, produite par DefinitionFactory (normalisée et validée).
 * `toArray()` est la forme archivée dans le manifest.
 */
final readonly class ModuleDefinition
{
    public const SCHEMA_VERSION = 1;

    /**
     * @param  list<FieldDefinition>  $fields
     * @param  list<RelationDefinition>  $relations
     */
    public function __construct(
        public int $schemaVersion,
        public ModuleKind $kind,
        public string $name,
        public string $singular,
        public string $model,
        public string $table,
        public string $slug,
        public string $icon,
        public MenuOptions $menu,
        public ModuleOptions $options,
        public array $fields,
        public array $relations,
        public bool $tree,
        public ?MorphOptions $morph,
        public ?PivotModule $pivot,
    ) {}

    /** @param  array<string, mixed>  $data  tableau normalisé et validé */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['schema_version'],
            ModuleKind::from($data['kind']),
            $data['name'],
            $data['singular'],
            $data['model'],
            $data['table'],
            $data['slug'],
            $data['icon'],
            MenuOptions::fromArray($data['menu']),
            ModuleOptions::fromArray($data['options']),
            array_map(FieldDefinition::fromArray(...), $data['fields']),
            array_map(RelationDefinition::fromArray(...), $data['relations']),
            $data['tree'],
            $data['morph'] === null ? null : MorphOptions::fromArray($data['morph']),
            $data['pivot'] === null ? null : PivotModule::fromArray($data['pivot']),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema_version' => $this->schemaVersion,
            'kind' => $this->kind->value,
            'name' => $this->name,
            'singular' => $this->singular,
            'model' => $this->model,
            'table' => $this->table,
            'slug' => $this->slug,
            'icon' => $this->icon,
            'menu' => $this->menu->toArray(),
            'options' => $this->options->toArray(),
            'fields' => array_map(fn (FieldDefinition $field) => $field->toArray(), $this->fields),
            'relations' => array_map(fn (RelationDefinition $relation) => $relation->toArray(), $this->relations),
            'tree' => $this->tree,
            'morph' => $this->morph?->toArray(),
            'pivot' => $this->pivot?->toArray(),
        ];
    }

    public function isPivot(): bool
    {
        return $this->kind === ModuleKind::Pivot;
    }

    public function field(string $name): ?FieldDefinition
    {
        foreach ($this->fields as $field) {
            if ($field->name === $name) {
                return $field;
            }
        }

        return null;
    }
}
