<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Registry;

use Amon\ModuleGenerator\Definition\ModuleDefinition;
use Amon\ModuleGenerator\Definition\PivotMode;
use Amon\ModuleGenerator\Manifest\Manifest;
use Amon\ModuleGenerator\Manifest\ManifestRepository;

/** Vue sur les manifests : liste des modules et détection des collisions. Rien de plus. */
final class ModuleRegistry
{
    public function __construct(private readonly ManifestRepository $manifests) {}

    /** @return list<Manifest> */
    public function modules(): array
    {
        return $this->manifests->all();
    }

    public function find(string $slug): ?Manifest
    {
        return $this->manifests->find($slug);
    }

    /**
     * Collisions d'une définition avec les modules enregistrés (y compris pending).
     *
     * @return array<string, list<string>> messages par chemin de la définition
     */
    public function collisions(ModuleDefinition $definition): array
    {
        $errors = [];
        $add = function (string $path, string $message) use (&$errors): void {
            $errors[$path][] = $message;
        };

        $tables = [];

        foreach ($this->modules() as $module) {
            if ($module->slug === $definition->slug) {
                $state = $module->isComplete() ? '' : ' (génération interrompue : nettoyer le manifest pending)';
                $add('slug', "Le module « {$definition->slug} » existe déjà{$state}.");
            }

            if ($module->model() === $definition->model) {
                $add('model', "Le modèle {$definition->model} appartient déjà au module « {$module->slug} ».");
            }

            foreach ([$module->table(), ...$module->pivotTables()] as $table) {
                if (is_string($table)) {
                    $tables[$table] = $module->slug;
                }
            }
        }

        if (isset($tables[$definition->table])) {
            $add('table', "La table {$definition->table} appartient déjà au module « {$tables[$definition->table]} ».");
        }

        foreach ($definition->relations as $i => $relation) {
            $pivot = $relation->pivot;

            if ($pivot === null) {
                continue;
            }

            if ($pivot->mode === PivotMode::Generate && isset($tables[(string) $pivot->table])) {
                $add("relations.{$i}.pivot.table", "La table {$pivot->table} appartient déjà au module « {$tables[(string) $pivot->table]} ».");
            }

            if ($pivot->mode === PivotMode::Module) {
                $target = $this->find((string) $pivot->module);

                if ($target === null || ! $target->isComplete()) {
                    $add("relations.{$i}.pivot.module", "Module pivot « {$pivot->module} » introuvable.");
                } elseif (($target->definition['kind'] ?? null) !== 'pivot') {
                    $add("relations.{$i}.pivot.module", "Le module « {$pivot->module} » n'est pas un module pivot.");
                }
            }
        }

        return $errors;
    }

    /**
     * Modèles connus pour les listes de cibles de l'UI : modèles des modules complets.
     *
     * @return list<array{model: string, slug: string, kind: string}>
     */
    public function models(): array
    {
        $models = [];

        foreach ($this->modules() as $module) {
            if ($module->isComplete() && is_string($module->model())) {
                $models[] = ['model' => $module->model(), 'slug' => $module->slug, 'kind' => (string) ($module->definition['kind'] ?? 'standard')];
            }
        }

        return $models;
    }
}
