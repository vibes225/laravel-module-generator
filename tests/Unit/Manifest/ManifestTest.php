<?php

use Amon\ModuleGenerator\Manifest\ManifestRepository;
use Amon\ModuleGenerator\Manifest\ManifestStatus;
use Amon\ModuleGenerator\Registry\ModuleRegistry;
use Amon\ModuleGenerator\Support\Hasher;

it('calcule une empreinte indépendante du BOM et des fins de ligne', function () {
    expect(Hasher::hash("a\r\nb"))->toBe(Hasher::hash("\xEF\xBB\xBFa\nb"))
        ->and(Hasher::hash("a\rb"))->toBe(Hasher::hash("a\nb"));
});

it('écrit, relit, liste et supprime des manifests', function () {
    $repository = new ManifestRepository(sandboxPath());
    $manifest = manifestFor(clientInput());

    $repository->save($manifest);
    $repository->save(manifestFor(clientInput(['singular' => 'Article'])));

    expect($repository->find('clients')->toArray())->toBe($manifest->toArray())
        ->and(array_map(fn ($m) => $m->slug, $repository->all()))->toBe(['articles', 'clients'])
        ->and(json_decode(file_get_contents($repository->modulePath('clients')), true)['module'])
        ->toBe(['slug' => 'clients', 'name' => 'Clients', 'model' => 'Client', 'table' => 'clients', 'kind' => 'standard']);

    $repository->delete('clients');
    expect($repository->find('clients'))->toBeNull();
});

it('archive un manifest et le retrouve par slug, nom ou chemin', function () {
    $repository = new ManifestRepository(sandboxPath());
    $repository->save(manifestFor(clientInput()));
    $first = $repository->archive('clients', '20260101-100000');

    $repository->save(manifestFor(clientInput(['icon' => 'users'])));
    $repository->archive('clients', '20260201-100000');

    expect($repository->find('clients'))->toBeNull()
        ->and($repository->findArchived('clients')->definition['icon'])->toBe('users')
        ->and($repository->findArchived('clients-20260101-100000')->definition['icon'])->toBe('folder')
        ->and($repository->findArchived($first)->slug)->toBe('clients')
        ->and($repository->findArchived('nope'))->toBeNull();
});

it('ne signale rien pour un module nouveau', function () {
    $repository = new ManifestRepository(sandboxPath());
    $repository->save(manifestFor(clientInput()));

    expect((new ModuleRegistry($repository))->collisions(definitionFactory()->make(clientInput(['singular' => 'Invoice']))))->toBe([]);
});

it('signale un manifest pending comme collision de slug', function () {
    $repository = new ManifestRepository(sandboxPath());
    $repository->save(manifestFor(clientInput(), ManifestStatus::Pending));

    expect((new ModuleRegistry($repository))->collisions(definitionFactory()->make(clientInput()))['slug'][0])->toContain('pending');
});

it('signale les collisions avec les modules enregistrés', function () {
    $repository = new ManifestRepository(sandboxPath());
    $repository->save(manifestFor(clientInput(['relations' => [
        ['type' => 'belongsToMany', 'target' => 'Tag', 'pivot' => ['mode' => 'generate']],
    ]])));
    $repository->save(manifestFor([
        'kind' => 'pivot',
        'singular' => 'Invoice tag',
        'pivot' => ['left' => ['model' => 'Invoice'], 'right' => ['model' => 'Tag']],
    ]));
    $registry = new ModuleRegistry($repository);

    $collisions = $registry->collisions(definitionFactory()->make(clientInput([
        'singular' => 'Customer',
        'model' => 'Client',
        'table' => 'client_tag',
        'slug' => 'clients',
        'relations' => [
            ['type' => 'belongsToMany', 'target' => 'Label', 'pivot' => ['mode' => 'generate', 'table' => 'clients']],
            ['type' => 'belongsToMany', 'target' => 'Tag', 'name' => 'viaModule', 'pivot' => ['mode' => 'module', 'module' => 'invoice-tag', 'model' => 'InvoiceTag', 'table' => 'invoice_tag']],
            ['type' => 'belongsToMany', 'target' => 'Tag', 'name' => 'missing', 'pivot' => ['mode' => 'module', 'module' => 'nope', 'model' => 'Nope', 'table' => 'nope']],
            ['type' => 'belongsToMany', 'target' => 'Tag', 'name' => 'notPivot', 'pivot' => ['mode' => 'module', 'module' => 'clients', 'model' => 'Client', 'table' => 'clients']],
        ],
    ])));

    expect(array_keys($collisions))->toEqualCanonicalizing([
        'slug', 'model', 'table', 'relations.0.pivot.table', 'relations.2.pivot.module', 'relations.3.pivot.module',
    ])->and($registry->models())->toHaveCount(2);
});
