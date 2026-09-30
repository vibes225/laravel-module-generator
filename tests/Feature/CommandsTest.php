<?php

use Illuminate\Support\Facades\Artisan;

it('affiche le plan en dry-run sans rien écrire', function () {
    $base = $this->useSandboxProject();

    $this->artisan('module-generator:make', ['name' => 'Client', '--field' => ['name:string', 'email:email:unique'], '--dry-run' => true, '--no-interaction' => true])
        ->expectsOutputToContain('app/Models/Client.php')
        ->expectsOutputToContain('Dry-run')
        ->assertSuccessful();

    expect(is_dir($base.'/app/Models'))->toBeFalse()
        ->and(is_dir($base.'/.module-generator/modules'))->toBeFalse();
});

it('génère exactement le plan annoncé par le dry-run', function () {
    $base = $this->useSandboxProject();
    $arguments = ['name' => 'Invoice', '--field' => ['number:string:unique', 'status:enum(draft,paid)'], '--relation' => ['belongsTo:Client'], '--no-interaction' => true];

    expect(Artisan::call('module-generator:make', [...$arguments, '--dry-run' => true, '--format' => 'json']))->toBe(0);
    $plan = json_decode(Artisan::output(), true);

    $this->artisan('module-generator:make', $arguments)->assertSuccessful();

    foreach ($plan['files'] as $file) {
        expect(file_get_contents($base.'/'.$file['path']))->toBe($file['content']);
    }

    $manifest = json_decode(file_get_contents($base.'/.module-generator/modules/invoices.json'), true);
    expect(array_column($manifest['files'], 'path'))->toBe(array_column($plan['files'], 'path'));
});

it('liste, supprime puis régénère un module depuis son archive', function () {
    $base = $this->useSandboxProject();

    $this->artisan('module-generator:make', ['name' => 'Client', '--field' => ['name:string'], '--no-interaction' => true])->assertSuccessful();
    $this->artisan('module-generator:list')->expectsOutputToContain('clients')->assertSuccessful();

    $this->artisan('module-generator:remove', ['slug' => 'clients', '--force' => true, '--no-interaction' => true])
        ->expectsOutputToContain('Définition archivée')
        ->assertSuccessful();
    expect(is_file($base.'/app/Models/Client.php'))->toBeFalse();

    $this->artisan('module-generator:make', ['--from' => 'clients', '--no-interaction' => true])->assertSuccessful();
    expect(is_file($base.'/app/Models/Client.php'))->toBeTrue();
});

it('refuse de générer un module en conflit', function () {
    $this->useSandboxProject();

    $this->artisan('module-generator:make', ['name' => 'Client', '--field' => ['name:string'], '--no-interaction' => true])->assertSuccessful();
    $this->artisan('module-generator:make', ['name' => 'Client', '--field' => ['name:string'], '--no-interaction' => true])
        ->expectsOutputToContain('Génération impossible')
        ->assertFailed();
});

it('affiche les erreurs de définition', function () {
    $this->useSandboxProject();

    $this->artisan('module-generator:make', ['name' => 'Client', '--field' => ['Bad Name:string'], '--no-interaction' => true])
        ->expectsOutputToContain('fields.0.name')
        ->assertFailed();

    $this->artisan('module-generator:make', ['name' => 'Client', '--field' => ['name:string:bogus'], '--no-interaction' => true])
        ->expectsOutputToContain('Modificateur inconnu')
        ->assertFailed();
});

it('refuse de s\'exécuter en production, sauf en dry-run', function () {
    $this->useSandboxProject();
    $this->app['env'] = 'production';

    $this->artisan('module-generator:make', ['name' => 'Client', '--field' => ['name:string'], '--no-interaction' => true])
        ->expectsOutputToContain('refusée en production')
        ->assertFailed();

    $this->artisan('module-generator:make', ['name' => 'Client', '--field' => ['name:string'], '--dry-run' => true, '--no-interaction' => true])
        ->assertSuccessful();
    $this->artisan('module-generator:remove', ['slug' => 'clients', '--no-interaction' => true])->assertFailed();
});
