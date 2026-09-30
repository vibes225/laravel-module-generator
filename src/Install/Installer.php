<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Install;

use Amon\ModuleGenerator\Support\AtomicFile;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

/**
 * Installation unique : publie l'échafaudage (sans jamais écraser un fichier existant), ajoute une ligne
 * à routes/web.php et une prop partagée à HandleInertiaRequests (avec marqueur), vérifie l'hôte.
 */
final class Installer
{
    public const MARKER = 'module-generator';

    private const ROUTES_FILE = 'routes/web.php';

    private const SHARE_FILE = 'app/Http/Middleware/HandleInertiaRequests.php';

    private const SHARE_ANCHOR = '...parent::share($request),';

    private const COMPOSER_PACKAGES = ['inertiajs/inertia-laravel', 'tightenco/ziggy'];

    private const NPM_PACKAGES = ['react', 'react-dom', '@inertiajs/react', 'lucide-react'];

    public function __construct(
        private readonly string $basePath,
        private readonly string $scaffoldPath,
        private readonly string $convention = 'breeze',
    ) {}

    public function plan(): InstallPlan
    {
        return new InstallPlan(
            $this->scaffoldFiles(),
            $this->routesStatus(),
            $this->shareStatus(),
            $this->warnings(),
            $this->commands(),
        );
    }

    /** @return list<string> actions réalisées */
    public function execute(InstallPlan $plan, bool $editShared): array
    {
        $done = [];

        foreach ($plan->filesToCopy() as $path) {
            AtomicFile::write($this->path($path), (string) file_get_contents($this->scaffoldPath.'/'.$path));
            $done[] = "Publié : {$path}";
        }

        if (! $editShared) {
            return $done;
        }

        if ($plan->routes['status'] === InstallPlan::TODO) {
            $content = rtrim((string) file_get_contents($this->path(self::ROUTES_FILE)))."\n\n".$plan->routes['snippet']."\n";
            AtomicFile::write($this->path(self::ROUTES_FILE), $content);
            $done[] = 'Modifié : '.self::ROUTES_FILE;
        }

        if ($plan->share['status'] === InstallPlan::TODO) {
            $content = (string) file_get_contents($this->path(self::SHARE_FILE));
            $position = strpos($content, self::SHARE_ANCHOR);

            if ($position === false) {
                throw new RuntimeException('Ancre introuvable dans '.self::SHARE_FILE.'.');
            }

            $position += strlen(self::SHARE_ANCHOR);
            AtomicFile::write($this->path(self::SHARE_FILE), substr($content, 0, $position)."\n".$plan->share['snippet'].substr($content, $position));
            $done[] = 'Modifié : '.self::SHARE_FILE;
        }

        return $done;
    }

    private function path(string $relative): string
    {
        return $this->basePath.'/'.$relative;
    }

    /** @return list<array{path: string, exists: bool}> */
    private function scaffoldFiles(): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->scaffoldPath, RecursiveDirectoryIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            $path = str_replace(chr(92), '/', substr($file->getPathname(), strlen($this->scaffoldPath) + 1));

            // Un .gitkeep ne sert qu'à créer un dossier vide.
            if (basename($path) === '.gitkeep' && is_dir(dirname($this->path($path)))) {
                continue;
            }

            $files[] = ['path' => $path, 'exists' => file_exists($this->path($path))];
        }

        usort($files, fn (array $a, array $b) => strcmp($a['path'], $b['path']));

        return $files;
    }

    /** @return array{status: string, file: string, snippet: string} */
    private function routesStatus(): array
    {
        $content = is_file($this->path(self::ROUTES_FILE)) ? (string) file_get_contents($this->path(self::ROUTES_FILE)) : null;

        return [
            'status' => match (true) {
                $content === null => InstallPlan::MANUAL,
                str_contains($content, 'admin-modules.php') => InstallPlan::DONE,
                default => InstallPlan::TODO,
            },
            'file' => self::ROUTES_FILE,
            'snippet' => '// '.self::MARKER."\nrequire __DIR__.'/admin-modules.php';",
        ];
    }

    /** @return array{status: string, file: string, snippet: string} */
    private function shareStatus(): array
    {
        $content = is_file($this->path(self::SHARE_FILE)) ? (string) file_get_contents($this->path(self::SHARE_FILE)) : null;
        $snippet = implode("\n", [
            '            // '.self::MARKER.':start',
            "            'admin' => fn () => [",
            "                'menu' => app(".'\App\Support\Admin\MenuRegistry::class)->forUser($request->user()),',
            "                'flash' => [",
            "                    'success' => \$request->session()->get('success'),",
            "                    'error' => \$request->session()->get('error'),",
            '                ],',
            '            ],',
            '            // '.self::MARKER.':end',
        ]);

        return [
            'status' => match (true) {
                $content === null => InstallPlan::MANUAL,
                str_contains($content, self::MARKER.':start') => InstallPlan::DONE,
                str_contains($content, self::SHARE_ANCHOR) => InstallPlan::TODO,
                default => InstallPlan::MANUAL,
            },
            'file' => self::SHARE_FILE,
            'snippet' => $snippet,
        ];
    }

    /** @return list<string> vérifications de l'hôte (affichées, jamais corrigées automatiquement) */
    private function warnings(): array
    {
        $warnings = [];
        $directory = $this->convention === 'starter-kit' ? 'pages' : 'Pages';
        $entry = null;

        foreach (['resources/js/app.jsx', 'resources/js/app.js', 'resources/js/app.tsx', 'resources/js/app.ts'] as $candidate) {
            if (is_file($this->path($candidate))) {
                $entry = $candidate;
                break;
            }
        }

        if ($entry === null) {
            $warnings[] = 'Point d\'entrée Inertia introuvable (resources/js/app.jsx).';
        } elseif (! str_contains((string) file_get_contents($this->path($entry)), '.jsx')) {
            $warnings[] = "Le résolveur de pages de {$entry} doit accepter les fichiers .jsx, par exemple :\n"
                ."    const pages = import.meta.glob('./{$directory}/**/*.jsx');\n"
                ."    resolve: (name) => pages[`./{$directory}/\${name}.jsx`](),";
        }

        $view = $this->path('resources/views/app.blade.php');

        if (is_file($view) && ! str_contains((string) file_get_contents($view), '@routes')) {
            $warnings[] = 'Ajouter la directive @routes (Ziggy) dans resources/views/app.blade.php : les pages générées utilisent route().';
        }

        return $warnings;
    }

    /** @return list<string> dépendances de production manquantes */
    private function commands(): array
    {
        $commands = [];
        $composer = json_decode((string) @file_get_contents($this->path('composer.json')), true);
        $missing = array_filter(self::COMPOSER_PACKAGES, fn (string $package) => ! isset($composer['require'][$package]));

        if ($missing !== []) {
            $commands[] = 'composer require '.implode(' ', $missing);
        }

        $npm = json_decode((string) @file_get_contents($this->path('package.json')), true);
        $missing = array_filter(self::NPM_PACKAGES, fn (string $package) => ! isset($npm['dependencies'][$package]));

        if ($missing !== []) {
            $commands[] = 'npm install '.implode(' ', $missing);
        }

        return $commands;
    }
}
