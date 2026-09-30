<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Definition;

final readonly class ModuleOptions
{
    public function __construct(
        public bool $timestamps,
        public bool $softDeletes,
        public int $perPage,
        public string $sortField,
        public string $sortDirection,
    ) {}

    /** @param  array<string, mixed>  $data */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['timestamps'], $data['soft_deletes'], $data['per_page'],
            $data['default_sort']['field'], $data['default_sort']['direction'],
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'timestamps' => $this->timestamps,
            'soft_deletes' => $this->softDeletes,
            'per_page' => $this->perPage,
            'default_sort' => ['field' => $this->sortField, 'direction' => $this->sortDirection],
        ];
    }
}
