<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Definition;

/** Un côté d'un module pivot. Côté polymorphe : décrit par l'option `morph` du module (pas de modèle ni de FK). */
final readonly class PivotSide
{
    public function __construct(
        public ?string $model,
        public ?string $foreignKey,
        public string $onDelete,
        public bool $polymorphic,
    ) {}

    /** @param  array<string, mixed>  $data */
    public static function fromArray(array $data): self
    {
        return new self($data['model'], $data['foreign_key'], $data['on_delete'], $data['polymorphic']);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'model' => $this->model,
            'foreign_key' => $this->foreignKey,
            'on_delete' => $this->onDelete,
            'polymorphic' => $this->polymorphic,
        ];
    }
}
