<?php

use Amon\ModuleGenerator\Manifest\ManifestStatus;
use Amon\ModuleGenerator\Support\Hasher;

it('génère les fichiers et un manifest complet avec empreintes', function () {
    $base = installedSandbox();
    $service = moduleService($base);
    $plan = $service->plan($service->definition(clientInput()));

    $manifest = $service->generate($plan);

    expect($manifest->status)->toBe(ManifestStatus::Complete)
        ->and(count($manifest->files))->toBe(count($plan->files))
        ->and($service->registry()->find('clients')->isComplete())->toBeTrue();

    foreach ($manifest->files as $file) {
        expect(Hasher::hash(file_get_contents($base.'/'.$file->path)))->toBe($file->sha256);
    }

    $again = $service->plan($service->definition(clientInput()));
    expect($again->isExecutable())->toBeFalse()->and($again->conflicts)->toHaveKey('slug');
});

it('annule tout en cas d\'erreur d\'écriture', function () {
    $base = installedSandbox();
    file_put_contents($base.'/routes', 'un fichier bloque le dossier routes/');
    $service = moduleService($base);
    $plan = $service->plan($service->definition(clientInput()));

    expect(fn () => $service->generate($plan))->toThrow(RuntimeException::class, 'annulée');
    expect(is_file($base.'/app/Models/Client.php'))->toBeFalse()
        ->and(is_dir($base.'/app/Models'))->toBeFalse()
        ->and($service->registry()->find('clients'))->toBeNull();
});

it('refuse un plan non exécutable', function () {
    $service = moduleService(sandboxPath());

    $service->generate($service->plan($service->definition(clientInput())));
})->throws(RuntimeException::class, 'non exécutable');

it('refuse une génération concurrente (verrou)', function () {
    $base = installedSandbox();
    $service = moduleService($base);
    $plan = $service->plan($service->definition(clientInput()));
    mkdir($base.'/.module-generator', 0777, true);
    $handle = fopen($base.'/.module-generator/.lock', 'c');
    flock($handle, LOCK_EX);

    try {
        $service->generate($plan);
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
})->throws(RuntimeException::class, 'en cours');
