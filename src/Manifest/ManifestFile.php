<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Manifest;

final readonly class ManifestFile
{
    /** @param  string  $path  relatif à la racine du projet, séparateurs `/` */
    public function __construct(
        public string $path,
        public string $kind,
        public ?string $sha256,
    ) {}

    /** @param  array<string, mixed>  $data */
    public static function fromArray(array $data): self
    {
        return new self((string) $data['path'], (string) $data['kind'], isset($data['sha256']) ? (string) $data['sha256'] : null);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['path' => $this->path, 'kind' => $this->kind, 'sha256' => $this->sha256];
    }
}
