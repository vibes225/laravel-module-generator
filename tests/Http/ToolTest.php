<?php

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Artisan;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->base = $this->useSandboxProject();
    $this->travelTo(new DateTimeImmutable('2026-09-30 10:00:00'));
});

function invoiceDefinition(): array
{
    return [
        'singular' => 'Invoice',
        'fields' => [
            ['name' => 'number', 'type' => 'string', 'unique' => true],
            ['name' => 'status', 'type' => 'enum', 'options' => ['values' => ['draft', 'paid']]],
        ],
        'relations' => [['type' => 'belongsTo', 'target' => 'Client']],
    ];
}

it('affiche la liste des modules et le constructeur', function () {
    $this->get('/module-generator')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->component('Modules')->has('modules', 0));
    $this->get('/module-generator/create')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->component('Builder')->has('types', 17));
    $this->get('/module-generator/assets/app.js')->assertOk()->assertHeader('Content-Type', 'application/javascript; charset=utf-8');
    $this->get('/module-generator/assets/../../composer.json')->assertNotFound();
});

it('renvoie les erreurs de définition par chemin', function () {
    $this->postJson('/module-generator/api/preview', ['definition' => ['fields' => [['name' => 'Bad', 'type' => 'string']]]])
        ->assertStatus(422)
        ->assertJsonPath('errors.singular.0', 'Texte requis.')
        ->assertJsonMissingPath('errors.model')
        ->assertJsonStructure(['errors' => ['fields.0.name']]);
});

it('produit exactement le même plan que la CLI', function () {
    $http = $this->postJson('/module-generator/api/preview', ['definition' => invoiceDefinition()])->assertOk()->json('plan');

    file_put_contents($this->base.'/definition.json', json_encode(invoiceDefinition()));
    Artisan::call('module-generator:make', ['--json' => $this->base.'/definition.json', '--dry-run' => true, '--format' => 'json', '--no-interaction' => true]);
    $cli = json_decode(Artisan::output(), true);

    expect($http)->toBe($cli);
});

it('génère, refuse les conflits, puis supprime en protégeant les fichiers modifiés', function () {
    $this->postJson('/module-generator/api/generate', ['definition' => invoiceDefinition()])->assertCreated()->assertJsonPath('manifest.status', 'complete');
    expect(is_file($this->base.'/app/Models/Invoice.php'))->toBeTrue();

    $this->postJson('/module-generator/api/generate', ['definition' => invoiceDefinition()])->assertStatus(409)->assertJsonStructure(['plan' => ['conflicts' => ['slug']]]);

    file_put_contents($this->base.'/app/Models/Invoice.php', "\n// modifié", FILE_APPEND);
    $this->getJson('/module-generator/api/modules/invoices/removal')->assertOk()->assertJsonPath('plan.module', 'invoices');

    $this->deleteJson('/module-generator/api/modules/invoices')->assertOk()->assertJsonPath('result.kept', ['app/Models/Invoice.php']);
    expect(is_file($this->base.'/app/Models/Invoice.php'))->toBeTrue()
        ->and(is_file($this->base.'/app/Http/Controllers/Admin/InvoiceController.php'))->toBeFalse();

    $this->getJson('/module-generator/api/modules/invoices/removal')->assertNotFound();
});

it('refuse la génération et la suppression en production', function () {
    $this->app['env'] = 'production';
    $this->withoutMiddleware([ValidateCsrfToken::class, PreventRequestForgery::class]);

    $this->postJson('/module-generator/api/generate', ['definition' => invoiceDefinition()])->assertForbidden();
    $this->deleteJson('/module-generator/api/modules/invoices')->assertForbidden();
});
