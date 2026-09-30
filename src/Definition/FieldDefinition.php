<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Definition;

final readonly class FieldDefinition
{
    /** @param  array<string, mixed>  $options */
    public function __construct(
        public string $name,
        public string $label,
        public string $type,
        public bool $nullable,
        public bool $unique,
        public mixed $default,
        public bool $index,
        public array $options,
        public bool $searchable,
        public bool $sortable,
        public bool $filterable,
        public bool $inTable,
        public bool $inForm,
        public bool $inDetail,
    ) {}

    /** @param  array<string, mixed>  $data  tableau normalisé et validé */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['name'], $data['label'], $data['type'], $data['nullable'], $data['unique'], $data['default'],
            $data['index'], $data['options'], $data['searchable'], $data['sortable'], $data['filterable'],
            $data['in_table'], $data['in_form'], $data['in_detail'],
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'label' => $this->label,
            'type' => $this->type,
            'nullable' => $this->nullable,
            'unique' => $this->unique,
            'default' => $this->default,
            'index' => $this->index,
            'options' => $this->options,
            'searchable' => $this->searchable,
            'sortable' => $this->sortable,
            'filterable' => $this->filterable,
            'in_table' => $this->inTable,
            'in_form' => $this->inForm,
            'in_detail' => $this->inDetail,
        ];
    }
}
