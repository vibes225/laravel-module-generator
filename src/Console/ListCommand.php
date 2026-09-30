<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Console;

use Amon\ModuleGenerator\Manifest\ManifestRepository;
use Illuminate\Console\Command;

final class ListCommand extends Command
{
    protected $signature = 'module-generator:list {--archived : Liste les définitions archivées}';

    protected $description = 'Liste les modules générés (manifests).';

    public function handle(ManifestRepository $manifests): int
    {
        if ($this->option('archived')) {
            $archives = array_map(fn (string $path) => [basename($path, '.json')], $manifests->archived());
            $this->table(['Archive (utilisable avec --from)'], $archives);

            return self::SUCCESS;
        }

        $rows = [];

        foreach ($manifests->all() as $manifest) {
            $rows[] = [
                $manifest->slug,
                $manifest->definition['name'] ?? '',
                $manifest->definition['model'] ?? '',
                $manifest->definition['kind'] ?? '',
                $manifest->isComplete() ? 'complet' : 'pending',
                count($manifest->files),
                $manifest->generatedAt,
            ];
        }

        if ($rows === []) {
            $this->components->info('Aucun module généré.');

            return self::SUCCESS;
        }

        $this->table(['Slug', 'Nom', 'Modèle', 'Nature', 'Statut', 'Fichiers', 'Généré le'], $rows);

        return self::SUCCESS;
    }
}
