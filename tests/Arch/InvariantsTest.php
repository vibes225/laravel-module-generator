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
