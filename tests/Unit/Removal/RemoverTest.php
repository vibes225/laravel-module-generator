<?php

use Amon\ModuleGenerator\Manifest\ManifestFile;
use Amon\ModuleGenerator\Manifest\ManifestRepository;
use Amon\ModuleGenerator\Manifest\ManifestStatus;
use Amon\ModuleGenerator\Removal\FileState;

function generatedSandbox(array $input = []): array
{
    $base = installedSandbox();
    $service = moduleService($base);
    $service->generate($service->plan($service->definition(clientInput($input))));

    return [$base, $service];
}

it('supprime les fichiers du module, les dossiers vides créés et archive la définition', function () {
    [$base, $service] = generatedSandbox();
    $plan = $service->removalPlan('clients');

    expect($plan->paths(FileState::Unchanged))->toHaveCount(count($plan->files));

    $result = $service->remove($plan, includeModified: false);

    expect($result['deleted'])->toHaveCount(count($plan->files))
        ->and(is_dir($base.'/app/Models'))->toBeFalse()
        ->and(is_dir($base.'/resources/js/Pages/Clients'))->toBeFalse()
        ->and(is_dir($base.'/app/Filters'))->toBeTrue()
        ->and($service->registry()->find('clients'))->toBeNull()
        ->and($service->archivedInput('clients')['model'])->toBe('Client');
});

it('conserve les fichiers modifiés sauf demande explicite, et signale les absents', function () {
    [$base, $service] = generatedSandbox();
    file_put_contents($base.'/app/Models/Client.php', "\n// modifié", FILE_APPEND);
    unlink($base.'/routes/modules/clients.php');

    $plan = $service->removalPlan('clients');
    expect($plan->paths(FileState::Modified))->toBe(['app/Models/Client.php'])
        ->and($plan->paths(FileState::Missing))->toBe(['routes/modules/clients.php']);

    $result = $service->remove($plan, includeModified: false);

    expect($result['kept'])->toBe(['app/Models/Client.php'])
        ->and($result['missing'])->toBe(['routes/modules/clients.php'])
        ->and(is_file($base.'/app/Models/Client.php'))->toBeTrue();
});

it('supprime aussi les fichiers modifiés si demandé', function () {
    [$base, $service] = generatedSandbox();
    file_put_contents($base.'/app/Models/Client.php', "\n// modifié", FILE_APPEND);

    $service->remove($service->removalPlan('clients'), includeModified: true);

    expect(is_file($base.'/app/Models/Client.php'))->toBeFalse();
});

it('nettoie un manifest pending sans l\'archiver', function () {
    $base = installedSandbox();
    $service = moduleService($base);
    $manifest = manifestFor(clientInput(), ManifestStatus::Pending)
        ->withFiles([new ManifestFile('app/Models/Client.php', 'model', null)]);
    (new ManifestRepository($base.'/.module-generator'))->save($manifest);
    mkdir($base.'/app/Models', 0777, true);
    file_put_contents($base.'/app/Models/Client.php', '<?php');

    $plan = $service->removalPlan('clients');
    expect($plan->files[0]['state'])->toBe(FileState::Unknown);

    $result = $service->remove($plan, includeModified: false);

    expect($result['archive'])->toBeNull()
        ->and(is_file($base.'/app/Models/Client.php'))->toBeFalse()
        ->and($service->registry()->find('clients'))->toBeNull();
});

it('refuse un module inconnu', function () {
    moduleService(sandboxPath())->removalPlan('nope');
})->throws(RuntimeException::class, 'introuvable');
