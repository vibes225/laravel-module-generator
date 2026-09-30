<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Definition;

/** Pivot d'une relation belongsToMany : aucun, généré par le module, ou module pivot existant. */
final readonly class PivotSpec
{
    /** @param  list<FieldDefinition>  $fields  champs supplémentaires (mode generate) */
    public function __construct(
        public PivotMode $mode,
        public ?string $table,
        public bool $timestamps,
        public array $fields,
        public ?string $module,
    ) {}

    /** @param  array<string, mixed>  $data */
    public static function fromArray(array $data): self
    {
        return new self(
            PivotMode::from($data['mode']),
            $data['table'],
            $data['timestamps'],
            array_map(FieldDefinition::fromArray(...), $data['fields']),
            $data['module'],
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'mode' => $this->mode->value,
            'table' => $this->table,
            'timestamps' => $this->timestamps,
            'fields' => array_map(fn (FieldDefinition $field) => $field->toArray(), $this->fields),
            'module' => $this->module,
        ];
    }
}
