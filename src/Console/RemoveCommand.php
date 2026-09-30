<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Console;

use Amon\ModuleGenerator\Console\Concerns\PrintsPlans;
use Amon\ModuleGenerator\ModuleService;
use Amon\ModuleGenerator\Removal\FileState;
use Illuminate\Console\Command;
use RuntimeException;

use function Laravel\Prompts\confirm;

final class RemoveCommand extends Command
{
    use PrintsPlans;

    protected $signature = 'module-generator:remove
        {slug : Slug du module}
        {--include-modified : Supprimer aussi les fichiers modifiés depuis la génération (avec confirmation)}
        {--force : Sans confirmation (scripts)}
        {--dry-run : Affiche ce qui serait supprimé}';

    protected $description = 'Supprime les fichiers d\'un module (listés dans son manifest) et archive sa définition.';

    public function handle(ModuleService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($this->refuseInProduction($dryRun)) {
            return self::FAILURE;
        }

        try {
            $plan = $service->removalPlan((string) $this->argument('slug'));
        } catch (RuntimeException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $labels = [
            FileState::Unchanged->value => '<fg=green>inchangé</>',
            FileState::Modified->value => '<fg=yellow>modifié</>',
            FileState::Missing->value => '<fg=gray>absent</>',
            FileState::Unknown->value => '<fg=yellow>génération interrompue</>',
        ];

        foreach ($plan->files as $file) {
            $this->components->twoColumnDetail($file['path'], $labels[$file['state']->value]);
        }

        $includeModified = (bool) $this->option('include-modified');

        if ($plan->hasModified()) {
            $this->components->warn($includeModified
                ? 'Les fichiers modifiés seront supprimés.'
                : 'Les fichiers modifiés seront conservés (--include-modified pour les supprimer).');
        }

        if ($dryRun) {
            $this->components->info('Dry-run : rien n\'a été supprimé.');

            return self::SUCCESS;
        }

        if ($includeModified && $plan->hasModified() && ! $this->option('force')) {
            if (! $this->input->isInteractive() || ! confirm('Supprimer définitivement les fichiers modifiés ?', default: false)) {
                $this->components->error('Suppression annulée (confirmer, ou --force pour les scripts).');

                return self::FAILURE;
            }
        } elseif ($this->input->isInteractive() && ! $this->option('force') && ! confirm("Supprimer le module « {$plan->manifest->slug} » ?", default: true)) {
            return self::FAILURE;
        }

        $result = $service->remove($plan, $includeModified);

        $this->components->info(count($result['deleted']).' fichier(s) supprimé(s).');
        $this->components->bulletList([
            ...array_map(fn (string $path) => "Conservé (modifié) : {$path}", $result['kept']),
            ...array_map(fn (string $path) => "Absent (ignoré) : {$path}", $result['missing']),
            $result['archive'] === null ? 'Manifest pending nettoyé.' : 'Définition archivée : '.basename($result['archive']).' (régénérer avec --from)',
            'La table reste en base : annuler la migration relève du développeur.',
        ]);

        return self::SUCCESS;
    }
}
