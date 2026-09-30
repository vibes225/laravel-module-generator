<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Definition;

/** Relation purement déclarative : seul ce qui appartient au module est généré. */
final readonly class RelationDefinition
{
    public function __construct(
        public RelationType $type,
        public string $name,
        public string $label,
        public string $target,
        public ?string $targetTable,
        public ?string $foreignKey,
        public bool $nullable,
        public ?string $onDelete,
        public ?string $display,
        public bool $inTable,
        public bool $inForm,
        public ?string $foreignPivotKey,
        public ?string $relatedPivotKey,
        public ?PivotSpec $pivot,
    ) {}

    /** @param  array<string, mixed>  $data */
    public static function fromArray(array $data): self
    {
        return new self(
            RelationType::from($data['type']), $data['name'], $data['label'], $data['target'], $data['target_table'],
            $data['foreign_key'], $data['nullable'], $data['on_delete'], $data['display'], $data['in_table'],
            $data['in_form'], $data['foreign_pivot_key'], $data['related_pivot_key'],
            $data['pivot'] === null ? null : PivotSpec::fromArray($data['pivot']),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'name' => $this->name,
            'label' => $this->label,
            'target' => $this->target,
            'target_table' => $this->targetTable,
            'foreign_key' => $this->foreignKey,
            'nullable' => $this->nullable,
            'on_delete' => $this->onDelete,
            'display' => $this->display,
            'in_table' => $this->inTable,
            'in_form' => $this->inForm,
            'foreign_pivot_key' => $this->foreignPivotKey,
            'related_pivot_key' => $this->relatedPivotKey,
            'pivot' => $this->pivot?->toArray(),
        ];
    }
}
