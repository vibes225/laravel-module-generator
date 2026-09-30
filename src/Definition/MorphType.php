<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Definition;

/** Type autorisé d'une relation polymorphe ; `class` est le nom de classe complet stocké en base. */
final readonly class MorphType
{
    public function __construct(
        public string $model,
        public string $class,
        public string $label,
        public string $displayColumn,
    ) {}

    /** @param  array<string, mixed>  $data */
    public static function fromArray(array $data): self
    {
        return new self($data['model'], $data['class'], $data['label'], $data['display_column']);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['model' => $this->model, 'class' => $this->class, 'label' => $this->label, 'display_column' => $this->displayColumn];
    }
}
