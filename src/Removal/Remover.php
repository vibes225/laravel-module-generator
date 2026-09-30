<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Removal;

use Amon\ModuleGenerator\Manifest\ManifestRepository;
use Amon\ModuleGenerator\Support\Hasher;
use RuntimeException;

/**
 * Supprimer un module = supprimer les fichiers de son manifest. Rien d'autre : pas de rollback de migration,
 * pas de recherche de références. Seule protection : un fichier modifié n'est supprimé que sur demande explicite.
 */
final class Remover
{
    public function __construct(
        private readonly string $basePath,
        private readonly ManifestRepository $manifests,
    ) {}

    public function plan(string $slug): RemovalPlan
    {
        $manifest = $this->manifests->find($slug) ?? throw new RuntimeException("Module « {$slug} » introuvable.");
        $files = [];

        foreach ($manifest->files as $file) {
            $path = $this->path($file->path);
            $state = match (true) {
                ! is_file($path) => FileState::Missing,
                $file->sha256 === null => FileState::Unknown,
                Hasher::hash((string) file_get_contents($path)) === $file->sha256 => FileState::Unchanged,
                default => FileState::Modified,
            };

            $files[] = ['path' => $file->path, 'kind' => $file->kind, 'state' => $state];
        }

        return new RemovalPlan($manifest, $files);
    }

    /**
     * @return array{deleted: list<string>, kept: list<string>, missing: list<string>, directories: list<string>, archive: ?string}
     */
    public function execute(RemovalPlan $plan, bool $includeModified, string $timestamp): array
    {
        $deleted = [];
        $kept = [];

        foreach ($plan->files as $file) {
            $deletable = match ($file['state']) {
                FileState::Unchanged, FileState::Unknown => true,
                FileState::Modified => $includeModified,
                FileState::Missing => false,
            };

            if ($deletable) {
                unlink($this->path($file['path']));
                $deleted[] = $file['path'];
            } elseif ($file['state'] === FileState::Modified) {
                $kept[] = $file['path'];
            }
        }

        $directories = [];
        $candidates = $plan->manifest->directories;
        usort($candidates, fn (string $a, string $b) => strlen($b) <=> strlen($a));

        foreach ($candidates as $directory) {
            $path = $this->path($directory);

            if (is_dir($path) && (scandir($path) ?: []) === ['.', '..']) {
                rmdir($path);
                $directories[] = $directory;
            }
        }

        // Manifest complet : archivé (régénération possible avec --from). Pending : simplement nettoyé.
        $archive = null;

        if ($plan->manifest->isComplete()) {
            $archive = $this->manifests->archive($plan->manifest->slug, $timestamp);
        } else {
            $this->manifests->delete($plan->manifest->slug);
        }

        return [
            'deleted' => $deleted,
            'kept' => $kept,
            'missing' => $plan->paths(FileState::Missing),
            'directories' => $directories,
            'archive' => $archive,
        ];
    }

    private function path(string $relative): string
    {
        return $this->basePath.'/'.$relative;
    }
}
