<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Removal;

use Amon\ModuleGenerator\Manifest\Manifest;

final readonly class RemovalPlan
{
    /** @param  list<array{path: string, kind: string, state: FileState}>  $files */
    public function __construct(
        public Manifest $manifest,
        public array $files,
    ) {}

    /** @return list<string> */
    public function paths(FileState $state): array
    {
        return array_values(array_map(
            fn (array $file) => $file['path'],
            array_filter($this->files, fn (array $file) => $file['state'] === $state),
        ));
    }

    public function hasModified(): bool
    {
        return $this->paths(FileState::Modified) !== [];
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'module' => $this->manifest->slug,
            'status' => $this->manifest->status->value,
            'files' => array_map(fn (array $file) => [...$file, 'state' => $file['state']->value], $this->files),
        ];
    }
}
