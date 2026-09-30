<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Manifest;

use Amon\ModuleGenerator\Definition\ModuleDefinition;

/** Trace d'un module généré : identité, statut, versions, définition normalisée et fichiers avec empreintes. */
final readonly class Manifest
{
    public const SCHEMA_VERSION = 1;

    /**
     * @param  array<string, mixed>  $definition  définition normalisée (ModuleDefinition::toArray())
     * @param  array<string, string>  $versions
     * @param  list<ManifestFile>  $files
     */
    public function __construct(
        public string $slug,
        public ManifestStatus $status,
        public array $versions,
        public string $generatedAt,
        public array $definition,
        public array $files,
    ) {}

    /**
     * @param  array<string, string>  $versions
     * @param  list<ManifestFile>  $files
     */
    public static function for(ModuleDefinition $definition, ManifestStatus $status, array $versions, string $generatedAt, array $files): self
    {
        return new self($definition->slug, $status, $versions, $generatedAt, $definition->toArray(), $files);
    }

    /** @param  array<string, mixed>  $data */
    public static function fromArray(array $data): self
    {
        return new self(
            (string) $data['module']['slug'],
            ManifestStatus::from($data['status']),
            $data['versions'] ?? [],
            (string) ($data['generated_at'] ?? ''),
            $data['definition'] ?? [],
            array_map(ManifestFile::fromArray(...), $data['files'] ?? []),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema_version' => self::SCHEMA_VERSION,
            'module' => [
                'slug' => $this->slug,
                'name' => $this->definition['name'] ?? null,
                'model' => $this->definition['model'] ?? null,
                'table' => $this->definition['table'] ?? null,
                'kind' => $this->definition['kind'] ?? null,
            ],
            'status' => $this->status->value,
            'versions' => $this->versions,
            'generated_at' => $this->generatedAt,
            'definition' => $this->definition,
            'files' => array_map(fn (ManifestFile $file) => $file->toArray(), $this->files),
        ];
    }

    public function withStatus(ManifestStatus $status): self
    {
        return new self($this->slug, $status, $this->versions, $this->generatedAt, $this->definition, $this->files);
    }

    /** @param  list<ManifestFile>  $files */
    public function withFiles(array $files): self
    {
        return new self($this->slug, $this->status, $this->versions, $this->generatedAt, $this->definition, $files);
    }

    public function isComplete(): bool
    {
        return $this->status === ManifestStatus::Complete;
    }

    public function model(): ?string
    {
        return $this->definition['model'] ?? null;
    }

    public function table(): ?string
    {
        return $this->definition['table'] ?? null;
    }

    /** Tables pivot générées par le module (relations belongsToMany en mode generate). */
    public function pivotTables(): array
    {
        $tables = [];

        foreach ($this->definition['relations'] ?? [] as $relation) {
            if (($relation['pivot']['mode'] ?? null) === 'generate' && isset($relation['pivot']['table'])) {
                $tables[] = $relation['pivot']['table'];
            }
        }

        return $tables;
    }
}
