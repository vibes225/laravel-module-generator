<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Definition;

final readonly class MorphOptions
{
    /** @param  list<MorphType>  $types  liste vide = tout modèle (colonnes hors formulaires) */
    public function __construct(
        public string $name,
        public KeyType $keyType,
        public bool $nullable,
        public array $types,
    ) {}

    /** @param  array<string, mixed>  $data */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['name'],
            KeyType::from($data['key_type']),
            $data['nullable'],
            array_map(MorphType::fromArray(...), $data['types']),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'key_type' => $this->keyType->value,
            'nullable' => $this->nullable,
            'types' => array_map(fn (MorphType $type) => $type->toArray(), $this->types),
        ];
    }
}
