<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Generation;

use Amon\ModuleGenerator\Registry\ModuleRegistry;

/**
 * Confronte un plan au projet (lecture seule) : fichiers existants, migration de la même table,
 * collisions du registre, installation et dépendances de l'hôte. Aucune option d'écrasement.
 */
final class PlanInspector
{
    public function __construct(
        private readonly string $basePath,
        private readonly ModuleRegistry $registry,
    ) {}

    public function inspect(GenerationPlan $plan): GenerationPlan
    {
        $conflicts = $this->registry->collisions($plan->definition);

        foreach ($plan->files as $file) {
            if (file_exists($this->basePath.'/'.$file->path)) {
                $conflicts[$file->path][] = 'Le fichier existe déjà.';
            }

            if ($file->kind === 'migration' && preg_match('/_create_(\w+)_table\.php$/', $file->path, $match)) {
                foreach (glob($this->basePath."/database/migrations/*_create_{$match[1]}_table.php") ?: [] as $existing) {
                    $conflicts[$file->path][] = 'Une migration crée déjà la table '.$match[1].' : '.basename($existing).'.';
                }
            }
        }

        return $plan->withProblems($conflicts, $this->missing($plan));
    }

    /** @return list<string> commandes à lancer avant de pouvoir générer */
    private function missing(GenerationPlan $plan): array
    {
        $missing = [];

        if (! is_file($this->basePath.'/app/Filters/QueryFilter.php')) {
            $missing[] = 'php artisan module-generator:install';
        }

        if ($plan->definition->tree && ! $this->requires('kalnoy/nestedset')) {
            $missing[] = 'composer require kalnoy/nestedset:"^6.0.7|^7.0"';
        }

        return $missing;
    }

    /** Dépendance de production de l'hôte (section `require` de composer.json). */
    private function requires(string $package): bool
    {
        $composer = json_decode((string) @file_get_contents($this->basePath.'/composer.json'), true);

        return is_array($composer) && isset($composer['require'][$package]);
    }
}
