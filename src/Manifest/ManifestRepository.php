<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Manifest;

use Amon\ModuleGenerator\Support\AtomicFile;
use RuntimeException;

/**
 * Manifests versionnés dans git : `<base>/modules/<slug>.json`, archives dans `<base>/archive/`.
 */
final class ManifestRepository
{
    public function __construct(private readonly string $basePath) {}

    public function basePath(): string
    {
        return $this->basePath;
    }

    /** @return list<Manifest> triés par slug */
    public function all(): array
    {
        $files = glob($this->modulesPath().'/*.json') ?: [];
        sort($files);

        return array_map(fn (string $file) => $this->read($file), $files);
    }

    public function find(string $slug): ?Manifest
    {
        $path = $this->modulePath($slug);

        return is_file($path) ? $this->read($path) : null;
    }

    public function save(Manifest $manifest): void
    {
        AtomicFile::write($this->modulePath($manifest->slug), $this->encode($manifest->toArray()));
    }

    /** Supprime le manifest actif (nettoyage d'un manifest pending). */
    public function delete(string $slug): void
    {
        $path = $this->modulePath($slug);

        if (is_file($path) && ! @unlink($path)) {
            throw new RuntimeException("Impossible de supprimer {$path}.");
        }
    }

    /** Déplace le manifest dans l'archive ; retourne le chemin archivé. */
    public function archive(string $slug, string $timestamp): string
    {
        $source = $this->modulePath($slug);

        if (! is_file($source)) {
            throw new RuntimeException("Aucun manifest pour le module {$slug}.");
        }

        $target = $this->archivePath()."/{$slug}-{$timestamp}.json";
        AtomicFile::write($target, (string) file_get_contents($source));
        unlink($source);

        return $target;
    }

    /** @return list<string> chemins des manifests archivés, du plus ancien au plus récent */
    public function archived(): array
    {
        $files = glob($this->archivePath().'/*.json') ?: [];
        sort($files);

        return $files;
    }

    /**
     * Retrouve une définition archivée : chemin de fichier, nom de fichier d'archive, ou slug (archive la plus récente).
     */
    public function findArchived(string $reference): ?Manifest
    {
        if (is_file($reference)) {
            return $this->read($reference);
        }

        $named = $this->archivePath().'/'.basename($reference, '.json').'.json';

        if (is_file($named)) {
            return $this->read($named);
        }

        $matches = array_filter($this->archived(), fn (string $file) => (bool) preg_match(
            '/^'.preg_quote($reference, '/').'-\d{8}-\d{6}\.json$/',
            basename($file),
        ));

        return $matches === [] ? null : $this->read((string) end($matches));
    }

    public function modulePath(string $slug): string
    {
        return $this->modulesPath()."/{$slug}.json";
    }

    private function modulesPath(): string
    {
        return $this->basePath.'/modules';
    }

    private function archivePath(): string
    {
        return $this->basePath.'/archive';
    }

    private function read(string $path): Manifest
    {
        $data = json_decode((string) file_get_contents($path), true);

        if (! is_array($data) || ! isset($data['module']['slug'], $data['status'])) {
            throw new RuntimeException("Manifest illisible : {$path}.");
        }

        return Manifest::fromArray($data);
    }

    /** @param  array<string, mixed>  $data */
    private function encode(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n";
    }
}
