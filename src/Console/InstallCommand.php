<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Console;

use Amon\ModuleGenerator\Console\Concerns\PrintsPlans;
use Amon\ModuleGenerator\Install\Installer;
use Amon\ModuleGenerator\Install\InstallPlan;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;

final class InstallCommand extends Command
{
    use PrintsPlans;

    protected $signature = 'module-generator:install
        {--dry-run : Affiche ce qui serait fait}
        {--yes : Accepte la modification de routes/web.php et HandleInertiaRequests sans question}';

    protected $description = 'Publie l\'échafaudage (kit UI, filtres, menu, routes) dans le projet. Une seule fois.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($this->refuseInProduction($dryRun)) {
            return self::FAILURE;
        }

        $installer = new Installer(base_path(), dirname(__DIR__, 2).'/scaffold', (string) config('module-generator.convention', 'breeze'));
        $plan = $installer->plan();

        $this->components->info('Échafaudage (appartient au projet, jamais écrasé) :');

        foreach ($plan->files as $file) {
            $this->components->twoColumnDetail($file['path'], $file['exists'] ? '<fg=gray>déjà présent, conservé</>' : '<fg=green>à publier</>');
        }

        foreach ([$plan->routes, $plan->share] as $edit) {
            $status = match ($edit['status']) {
                InstallPlan::DONE => '<fg=gray>déjà fait</>',
                InstallPlan::TODO => '<fg=yellow>modification (avec marqueur)</>',
                default => '<fg=red>à faire à la main</>',
            };
            $this->components->twoColumnDetail($edit['file'], $status);
        }

        foreach ($plan->warnings as $warning) {
            $this->components->warn($warning);
        }

        foreach ($plan->commands as $command) {
            $this->components->warn("Dépendance manquante : {$command}");
        }

        if ($dryRun) {
            $this->components->info('Dry-run : rien n\'a été modifié.');

            return self::SUCCESS;
        }

        $editShared = $plan->needsSharedEdits()
            && ($this->option('yes') || ($this->input->isInteractive() && confirm('Modifier routes/web.php (une ligne) et HandleInertiaRequests (une prop partagée) ?', default: true)));

        foreach ($installer->execute($plan, $editShared) as $action) {
            $this->components->twoColumnDetail($action, '<fg=green>OK</>');
        }

        foreach ([$plan->routes, $plan->share] as $edit) {
            if ($edit['status'] === InstallPlan::MANUAL || ($edit['status'] === InstallPlan::TODO && ! $editShared)) {
                $this->components->warn("À ajouter dans {$edit['file']} :");
                $this->line($edit['snippet']);
            }
        }

        $this->components->info('Installation terminée. Créer un module : php artisan module-generator:make');

        return self::SUCCESS;
    }
}
