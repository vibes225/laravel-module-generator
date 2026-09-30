<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Generation;

use Amon\ModuleGenerator\Generation\Formatting\Formatter;
use Amon\ModuleGenerator\Manifest\Manifest;
use Amon\ModuleGenerator\Manifest\ManifestFile;
use Amon\ModuleGenerator\Manifest\ManifestRepository;
use Amon\ModuleGenerator\Manifest\ManifestStatus;
use Amon\ModuleGenerator\Support\AtomicFile;
use Amon\ModuleGenerator\Support\Hasher;
use RuntimeException;
use Throwable;

/**
 * Seule étape avec écriture : verrou, manifest `pending`, écriture atomique de chaque fichier
 * (rollback complet en cas d'erreur), formatage optionnel, empreintes, manifest `complete`.
 */
final class PlanExecutor
{
    /** @var list<string> */
    private array $warnings = [];

    /** @param  array<string, string>  $versions */
    public function __construct(
        private readonly string $basePath,
        private readonly ManifestRepository $manifests,
        private readonly Formatter $formatter,
        private readonly array $versions,
    ) {}

    public function execute(GenerationPlan $plan, string $generatedAt): Manifest
    {
        if (! $plan->isExecutable()) {
            throw new RuntimeException('Plan non exécutable : résoudre les conflits et dépendances manquantes.');
        }

        $this->warnings = [];
        $lock = $this->lock();

        try {
            return $this->write($plan, $generatedAt);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /** @return list<string> avertissements de la dernière exécution (formatage) */
    public function warnings(): array
    {
        return $this->warnings;
    }

    private function write(GenerationPlan $plan, string $generatedAt): Manifest
    {
        foreach ($plan->files as $file) {
            if (file_exists($this->path($file->path))) {
                throw new RuntimeException("Le fichier {$file->path} est apparu depuis la prévisualisation.");
            }
        }

        $entries = array_map(fn (PlannedFile $file) => new ManifestFile($file->path, $file->kind, null), $plan->files);
        $manifest = Manifest::for($plan->definition, ManifestStatus::Pending, $this->versions, $generatedAt, $entries);
        $this->manifests->save($manifest);

        $written = [];
        $createdDirectories = [];

        try {
            foreach ($plan->files as $file) {
                $missing = $this->missingDirectories(dirname($this->path($file->path)));
                AtomicFile::write($this->path($file->path), $file->content);
                $createdDirectories = [...$createdDirectories, ...$missing];
                $written[] = $file->path;
            }
        } catch (Throwable $e) {
            $this->rollback($written, $createdDirectories);
            $this->manifests->delete($plan->definition->slug);

            throw new RuntimeException('Génération annulée, fichiers retirés : '.$e->getMessage(), previous: $e);
        }

        $this->warnings = $this->formatter->format($this->basePath, $written);

        $entries = array_map(fn (PlannedFile $file) => new ManifestFile(
            $file->path,
            $file->kind,
            Hasher::hash((string) file_get_contents($this->path($file->path))),
        ), $plan->files);

        $directories = array_map(fn (string $directory) => substr(str_replace(chr(92), '/', $directory), strlen($this->basePath) + 1), array_values(array_unique($createdDirectories)));
        $manifest = $manifest->withFiles($entries)->withDirectories($directories)->withStatus(ManifestStatus::Complete);
        $this->manifests->save($manifest);

        return $manifest;
    }

    /** @return resource */
    private function lock()
    {
        $directory = $this->manifests->basePath();

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $handle = fopen($directory.'/.lock', 'c');

        if ($handle === false || ! flock($handle, LOCK_EX | LOCK_NB)) {
            throw new RuntimeException('Une autre génération est en cours.');
        }

        return $handle;
    }

    /** @return list<string> dossiers à créer, du plus haut au plus profond */
    private function missingDirectories(string $directory): array
    {
        $missing = [];

        while (! is_dir($directory) && $directory !== dirname($directory)) {
            array_unshift($missing, $directory);
            $directory = dirname($directory);
        }

        return $missing;
    }

    /**
     * @param  list<string>  $written
     * @param  list<string>  $directories
     */
    private function rollback(array $written, array $directories): void
    {
        foreach ($written as $path) {
            if (is_file($this->path($path))) {
                unlink($this->path($path));
            }
        }

        foreach (array_reverse(array_unique($directories)) as $directory) {
            if (is_dir($directory) && (scandir($directory) ?: []) === ['.', '..']) {
                rmdir($directory);
            }
        }
    }

    private function path(string $relative): string
    {
        return $this->basePath.'/'.$relative;
    }
}
