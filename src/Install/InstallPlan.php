<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Install;

/** Ce que l'installation ferait : fichiers publiés, deux modifications partagées, vérifications de l'hôte. */
final readonly class InstallPlan
{
    public const DONE = 'done';

    public const TODO = 'todo';

    public const MANUAL = 'manual';

    /**
     * @param  list<array{path: string, exists: bool}>  $files
     * @param  array{status: string, file: string, snippet: string}  $routes
     * @param  array{status: string, file: string, snippet: string}  $share
     * @param  list<string>  $warnings  vérifications à corriger à la main (affichées, jamais réécrites)
     * @param  list<string>  $commands  dépendances manquantes (commandes à lancer)
     */
    public function __construct(
        public array $files,
        public array $routes,
        public array $share,
        public array $warnings,
        public array $commands,
    ) {}

    /** @return list<string> */
    public function filesToCopy(): array
    {
        return array_values(array_map(
            fn (array $file) => $file['path'],
            array_filter($this->files, fn (array $file) => ! $file['exists']),
        ));
    }

    public function needsSharedEdits(): bool
    {
        return $this->routes['status'] === self::TODO || $this->share['status'] === self::TODO;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'files' => $this->files,
            'routes' => $this->routes,
            'share' => $this->share,
            'warnings' => $this->warnings,
            'commands' => $this->commands,
        ];
    }
}
