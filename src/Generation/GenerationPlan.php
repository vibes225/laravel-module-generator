<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Generation;

use Amon\ModuleGenerator\Definition\ModuleDefinition;

/**
 * Résultat pur de la génération : fichiers prévus, notes, conflits et dépendances manquantes.
 * Affichable dans tous les cas ; exécutable seulement sans conflit ni dépendance manquante.
 */
final readonly class GenerationPlan
{
    /**
     * @param  list<PlannedFile>  $files
     * @param  list<string>  $notes
     * @param  array<string, list<string>>  $conflicts  messages par chemin (fichier ou champ de la définition)
     * @param  list<string>  $missing  dépendances manquantes (commande à lancer)
     */
    public function __construct(
        public ModuleDefinition $definition,
        public array $files,
        public array $notes = [],
        public array $conflicts = [],
        public array $missing = [],
    ) {}

    public function isExecutable(): bool
    {
        return $this->conflicts === [] && $this->missing === [];
    }

    /**
     * @param  array<string, list<string>>  $conflicts
     * @param  list<string>  $missing
     */
    public function withProblems(array $conflicts, array $missing): self
    {
        return new self($this->definition, $this->files, $this->notes, $conflicts, $missing);
    }

    public function file(string $path): ?PlannedFile
    {
        foreach ($this->files as $file) {
            if ($file->path === $path) {
                return $file;
            }
        }

        return null;
    }

    /** @return list<string> */
    public function paths(): array
    {
        return array_map(fn (PlannedFile $file) => $file->path, $this->files);
    }

    /** @return array<string, mixed> */
    public function toArray(bool $withContent = true): array
    {
        return [
            'module' => $this->definition->slug,
            'executable' => $this->isExecutable(),
            'files' => array_map(function (PlannedFile $file) use ($withContent) {
                $data = $file->toArray();

                if (! $withContent) {
                    unset($data['content']);
                }

                return $data;
            }, $this->files),
            'notes' => $this->notes,
            'conflicts' => $this->conflicts,
            'missing' => $this->missing,
        ];
    }
}
