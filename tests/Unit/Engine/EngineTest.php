<?php

use Amon\ModuleGenerator\Generation\GenerationSettings;
use Amon\ModuleGenerator\Generation\ModuleNames;
use Amon\ModuleGenerator\Stubs\StubRenderer;
use Amon\ModuleGenerator\Stubs\StubResolver;
use Amon\ModuleGenerator\Support\Escaper;

it('calcule le nommage selon la convention', function () {
    $definition = definitionFactory()->make(clientInput(['singular' => 'Client contact']));
    $breeze = new ModuleNames($definition, new GenerationSettings('breeze'));
    $kit = new ModuleNames($definition, new GenerationSettings('starter-kit'));

    expect($breeze->page('Index'))->toBe('ClientContacts/Index')
        ->and($breeze->pagePath('Index'))->toBe('resources/js/Pages/ClientContacts/Index.jsx')
        ->and($breeze->formPath())->toBe('resources/js/Pages/ClientContacts/partials/ClientContactForm.jsx')
        ->and($kit->page('Index'))->toBe('client-contacts/index')
        ->and($kit->formPath())->toBe('resources/js/pages/client-contacts/partials/client-contact-form.jsx')
        ->and($breeze->modelPath())->toBe('app/Models/ClientContact.php')
        ->and($breeze->routeName)->toBe('admin.client-contacts')
        ->and($breeze->routeParameter)->toBe('client_contact')
        ->and($breeze->enumClass('status'))->toBe('ClientContactStatus');
});

it('rend les stubs sans logique et refuse un placeholder sans valeur', function () {
    $project = sandboxPath();
    $package = sandboxPath();
    file_put_contents($package.'/demo.stub', 'Bonjour %%NAME%% {{ jsx }} %');
    file_put_contents($package.'/other.stub', 'package');
    file_put_contents($package.'/other.l12.stub', 'variante L12');
    file_put_contents($project.'/demo.stub', 'Projet %%NAME%%');

    expect((new StubRenderer(new StubResolver($package)))->render('demo', ['NAME' => 'A']))->toBe('Bonjour A {{ jsx }} %')
        ->and((new StubRenderer(new StubResolver($package, $project)))->render('demo', ['NAME' => 'B']))->toBe('Projet B')
        ->and((new StubRenderer(new StubResolver($package, null, 12)))->render('other', []))->toBe('variante L12')
        ->and((new StubRenderer(new StubResolver($package, null, 13)))->render('other', []))->toBe('package');

    (new StubRenderer(new StubResolver($package)))->render('demo', []);
})->throws(RuntimeException::class, '%%NAME%%');

it('échappe selon le contexte', function () {
    $b = chr(92);

    expect(Escaper::php("l'été ".$b))->toBe("'l".$b."'été ".$b.$b."'")
        ->and(Escaper::js("a'b\n</script>"))->toBe("'a".$b."'b".$b.'n<'.$b."/script>'")
        ->and(Escaper::jsx('<b>{x} & y</b>'))->toBe('&lt;b&gt;&#123;x&#125; &amp; y&lt;/b&gt;')
        ->and(Escaper::phpValue(true))->toBe('true')
        ->and(Escaper::phpValue(1.5))->toBe('1.5');

    Escaper::identifier('name; drop');
})->throws(InvalidArgumentException::class);

it('produit un plan pur et déterministe', function () {
    $base = installedSandbox();
    $service = moduleService($base);
    $definition = $service->definition(clientInput());

    $first = $service->plan($definition);
    $second = $service->plan($definition);

    expect($first->toArray())->toBe($second->toArray())
        ->and($first->isExecutable())->toBeTrue()
        ->and(is_dir($base.'/app/Models'))->toBeFalse();
});

it('rend le plan non exécutable en cas de conflit ou de dépendance manquante', function () {
    $base = installedSandbox();
    mkdir($base.'/app/Models', 0777, true);
    file_put_contents($base.'/app/Models/Client.php', '<?php');
    $service = moduleService($base);

    $plan = $service->plan($service->definition(clientInput(['tree' => true])));

    expect($plan->isExecutable())->toBeFalse()
        ->and($plan->conflicts)->toHaveKey('app/Models/Client.php')
        ->and($plan->missing)->toContain('composer require kalnoy/nestedset:"^6.0.7|^7.0"');

    $notInstalled = moduleService(sandboxPath());
    expect($notInstalled->plan($notInstalled->definition(clientInput()))->missing)->toContain('php artisan module-generator:install');
});

it('détecte une migration existante pour la même table', function () {
    $base = installedSandbox();
    mkdir($base.'/database/migrations', 0777, true);
    file_put_contents($base.'/database/migrations/2020_01_01_000000_create_clients_table.php', '<?php');
    $service = moduleService($base);

    $plan = $service->plan($service->definition(clientInput()));

    expect(array_keys($plan->conflicts))->toContain('database/migrations/2026_09_30_100000_create_clients_table.php');
});
