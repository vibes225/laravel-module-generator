<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Console;

use Illuminate\Console\Command;

final class PublishStubsCommand extends Command
{
    protected $signature = 'module-generator:publish-stubs {--force : Écraser les stubs déjà publiés}';

    protected $description = 'Publie les stubs dans le projet pour les personnaliser (le projet prime sur le package).';

    public function handle(): int
    {
        return $this->call('vendor:publish', ['--tag' => 'module-generator-stubs', '--force' => (bool) $this->option('force')]);
    }
}
