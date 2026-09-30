<?php

use Amon\ModuleGenerator\Install\Installer;
use Amon\ModuleGenerator\Install\InstallPlan;

function hostProject(bool $withDeps = true): string
{
    $base = sandboxPath();
    mkdir($base.'/routes', 0777, true);
    mkdir($base.'/app/Http/Middleware', 0777, true);
    mkdir($base.'/resources/js', 0777, true);
    mkdir($base.'/resources/views', 0777, true);
    file_put_contents($base.'/routes/web.php', "<?php\n\nuse Illuminate\Support\Facades\Route;\n");
    file_put_contents($base.'/app/Http/Middleware/HandleInertiaRequests.php', "<?php\n\nclass HandleInertiaRequests extends Middleware\n{\n    public function share(\$request): array\n    {\n        return [\n            ...parent::share(\$request),\n        ];\n    }\n}\n");
    file_put_contents($base.'/resources/js/app.jsx', "const pages = import.meta.glob('./Pages/**/*.jsx');\n");
    file_put_contents($base.'/resources/views/app.blade.php', "@routes\n@inertia\n");
    file_put_contents($base.'/composer.json', json_encode(['require' => $withDeps ? ['inertiajs/inertia-laravel' => '^3.0', 'tightenco/ziggy' => '^2.0'] : []]));
    file_put_contents($base.'/package.json', json_encode(['dependencies' => $withDeps ? ['react' => '*', 'react-dom' => '*', '@inertiajs/react' => '*', 'lucide-react' => '*'] : []]));

    return $base;
}

function installer(string $base): Installer
{
    return new Installer($base, dirname(__DIR__, 3).'/scaffold');
}

it('planifie la publication et les deux modifications partagées', function () {
    $plan = installer(hostProject())->plan();

    expect($plan->filesToCopy())->toContain('app/Filters/QueryFilter.php', 'app/Support/Admin/MenuRegistry.php', 'routes/admin-modules.php', 'resources/js/components/admin/AdminLayout.jsx')
        ->and($plan->routes['status'])->toBe(InstallPlan::TODO)
        ->and($plan->share['status'])->toBe(InstallPlan::TODO)
        ->and($plan->warnings)->toBe([])
        ->and($plan->commands)->toBe([]);
});

it('installe avec marqueurs, sans écraser, et reste idempotent', function () {
    $base = hostProject();
    mkdir($base.'/app/Filters', 0777, true);
    file_put_contents($base.'/app/Filters/QueryFilter.php', '<?php // version du projet');
    $installer = installer($base);

    $done = $installer->execute($installer->plan(), editShared: true);

    expect($done)->toContain('Modifié : routes/web.php', 'Modifié : app/Http/Middleware/HandleInertiaRequests.php')
        ->and(file_get_contents($base.'/app/Filters/QueryFilter.php'))->toBe('<?php // version du projet')
        ->and(file_get_contents($base.'/routes/web.php'))->toContain("// module-generator\nrequire __DIR__.'/admin-modules.php';")
        ->and(file_get_contents($base.'/app/Http/Middleware/HandleInertiaRequests.php'))->toContain('// module-generator:start', "'admin' => fn () => [");

    exec(PHP_BINARY.' -l '.escapeshellarg($base.'/app/Http/Middleware/HandleInertiaRequests.php'), $output, $code);
    expect($code)->toBe(0);

    $again = $installer->plan();
    expect($again->routes['status'])->toBe(InstallPlan::DONE)
        ->and($again->share['status'])->toBe(InstallPlan::DONE)
        ->and($again->filesToCopy())->toBe([])
        ->and($installer->execute($again, editShared: true))->toBe([]);
});

it('ne modifie pas les fichiers partagés sans accord', function () {
    $base = hostProject();
    $installer = installer($base);
    $installer->execute($installer->plan(), editShared: false);

    expect(file_get_contents($base.'/routes/web.php'))->not->toContain('admin-modules')
        ->and(is_file($base.'/routes/admin-modules.php'))->toBeTrue();
});

it('signale les vérifications et dépendances manquantes sans rien corriger', function () {
    $base = hostProject(withDeps: false);
    file_put_contents($base.'/resources/js/app.jsx', "const pages = import.meta.glob('./Pages/**/*.tsx');\n");
    file_put_contents($base.'/resources/views/app.blade.php', "@inertia\n");
    file_put_contents($base.'/app/Http/Middleware/HandleInertiaRequests.php', "<?php // share() personnalisé\n");

    $plan = installer($base)->plan();

    expect($plan->share['status'])->toBe(InstallPlan::MANUAL)
        ->and($plan->warnings)->toHaveCount(2)
        ->and($plan->warnings[0])->toContain('.jsx')
        ->and($plan->commands)->toBe([
            'composer require inertiajs/inertia-laravel tightenco/ziggy',
            'npm install react react-dom @inertiajs/react lucide-react',
        ]);
});
