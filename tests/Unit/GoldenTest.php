<?php

/*
 * Golden files : le code généré pour chaque définition de référence est comparé à un instantané versionné.
 * Mettre à jour après un changement voulu : UPDATE_GOLDEN=1 vendor/bin/pest tests/Unit/GoldenTest.php
 */

function goldenProfiles(): array
{
    return array_map(fn (string $file) => basename($file, '.json'), glob(__DIR__.'/../Fixtures/definitions/*.json'));
}

it('produit le code attendu', function (string $profile) {
    $input = json_decode(file_get_contents(__DIR__."/../Fixtures/definitions/{$profile}.json"), true);
    $plan = planFor($input);
    $root = __DIR__."/../Golden/{$profile}";

    if (getenv('UPDATE_GOLDEN')) {
        foreach ($plan->files as $file) {
            @mkdir(dirname("{$root}/{$file->path}"), 0777, true);
            file_put_contents("{$root}/{$file->path}", $file->content);
        }
    }

    $expected = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));

    foreach ($iterator as $file) {
        $expected[] = str_replace(DIRECTORY_SEPARATOR, '/', substr($file->getPathname(), strlen($root) + 1));
    }

    sort($expected);
    $paths = $plan->paths();
    sort($paths);

    expect($paths)->toBe($expected);

    foreach ($plan->files as $file) {
        expect($file->content)->toBe(str_replace("\r\n", "\n", file_get_contents("{$root}/{$file->path}")), $file->path);
    }
})->with(goldenProfiles());

it('produit du PHP syntaxiquement valide', function (string $profile) {
    $input = json_decode(file_get_contents(__DIR__."/../Fixtures/definitions/{$profile}.json"), true);
    $directory = sandboxPath();

    foreach (planFor($input)->files as $file) {
        if (str_ends_with($file->path, '.php')) {
            file_put_contents($target = $directory.'/'.basename($file->path), $file->content);
            exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($target).' 2>&1', $output, $code);
            expect($code)->toBe(0, $file->path.' : '.implode("\n", $output));
        }
    }
})->with(goldenProfiles());

it('ne référence jamais le package dans le code généré', function (string $profile) {
    $input = json_decode(file_get_contents(__DIR__."/../Fixtures/definitions/{$profile}.json"), true);

    foreach (planFor($input)->files as $file) {
        expect(preg_match('/Amon.ModuleGenerator|laravel-module-generator/i', $file->content))->toBe(0, $file->path);
    }
})->with(goldenProfiles());
