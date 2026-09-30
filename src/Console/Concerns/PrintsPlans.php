<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Console\Concerns;

use Amon\ModuleGenerator\Generation\GenerationPlan;

trait PrintsPlans
{
    protected function refuseInProduction(bool $dryRun): bool
    {
        if ($dryRun || ! $this->laravel->environment('production')) {
            return false;
        }

        $this->components->error('Outil de développement : exécution refusée en production (utiliser --dry-run).');

        return true;
    }

    protected function printPlan(GenerationPlan $plan, bool $withContent = false): void
    {
        $this->components->info("Module « {$plan->definition->name} » ({$plan->definition->slug}) : ".count($plan->files).' fichier(s).');

        foreach ($plan->files as $file) {
            $this->components->twoColumnDetail($file->path, $file->label);

            if ($withContent) {
                $this->line($file->content);
            }
        }

        foreach ($plan->notes as $note) {
            $this->components->warn($note);
        }

        foreach ($plan->conflicts as $path => $messages) {
            foreach ($messages as $message) {
                $this->components->error("{$path} : {$message}");
            }
        }

        foreach ($plan->missing as $command) {
            $this->components->error("Prérequis manquant, lancer : {$command}");
        }
    }

    /** @param  array<string, list<string>>  $errors */
    protected function printErrors(array $errors): void
    {
        $this->components->error('Définition invalide.');

        foreach ($errors as $path => $messages) {
            foreach ($messages as $message) {
                $this->components->twoColumnDetail($path, $message);
            }
        }
    }
}
