<?php

// Invariant : le code généré et l'échafaudage publié ne référencent jamais le package.
it('ne référence pas le namespace du package dans stubs et scaffold', function () {
    $root = dirname(__DIR__, 2);
    $offenders = [];

    foreach (['stubs', 'scaffold'] as $dir) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root.'/'.$dir, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if (preg_match('/Amon\\\\+ModuleGenerator|amon\/laravel-module-generator/i', (string) file_get_contents($file->getPathname()))) {
                $offenders[] = $file->getPathname();
            }
        }
    }

    expect($offenders)->toBe([]);
});

arch('les contrats sont des interfaces')
    ->expect('Amon\ModuleGenerator\Contracts')
    ->toBeInterfaces();

arch('le moteur ne dépend ni de HTTP ni de la console')
    ->expect(['Amon\ModuleGenerator\Definition', 'Amon\ModuleGenerator\Generation', 'Amon\ModuleGenerator\Fields', 'Amon\ModuleGenerator\Manifest', 'Amon\ModuleGenerator\Registry', 'Amon\ModuleGenerator\Removal'])
    ->not->toUse(['Illuminate\Http', 'Illuminate\Console', 'Illuminate\Support\Facades', 'Inertia', 'Amon\ModuleGenerator\Http', 'Amon\ModuleGenerator\Console']);

arch('les générateurs n\'accèdent pas au système de fichiers')
    ->expect(['Amon\ModuleGenerator\Generation\Generators', 'Amon\ModuleGenerator\Fields', 'Amon\ModuleGenerator\Definition'])
    ->not->toUse(['file_put_contents', 'file_get_contents', 'fopen', 'unlink', 'mkdir', 'rename', 'glob', 'is_file', 'file_exists', 'Amon\ModuleGenerator\Support\AtomicFile']);

arch('seul l\'exécuteur, les manifests, la suppression et l\'installation écrivent')
    ->expect('Amon\ModuleGenerator\Support\AtomicFile')
    ->toOnlyBeUsedIn(['Amon\ModuleGenerator\Generation\PlanExecutor', 'Amon\ModuleGenerator\Manifest', 'Amon\ModuleGenerator\Install']);

arch('les générateurs implémentent le contrat')
    ->expect('Amon\ModuleGenerator\Generation\Generators')
    ->classes()->toImplement('Amon\ModuleGenerator\Contracts\Generator')
    ->ignoring(['Amon\ModuleGenerator\Generation\Generators\ColumnBuilder', 'Amon\ModuleGenerator\Generation\Generators\FormBuilder']);

arch('pas d\'instructions de débogage')
    ->expect('Amon\ModuleGenerator')
    ->not->toUse(['dd', 'dump', 'var_dump', 'ray']);
