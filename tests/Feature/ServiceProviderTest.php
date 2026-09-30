<?php

use Amon\ModuleGenerator\ModuleGeneratorServiceProvider;
use Illuminate\Support\ServiceProvider;

it('fusionne la configuration par défaut', function () {
    expect(config('module-generator.convention'))->toBe('breeze')
        ->and(config('module-generator.naming.french_plural'))->toBeTrue()
        ->and(config('module-generator.format.prettier'))->toBeFalse();
});

it('déclare les quatre tags de publication', function () {
    $tags = ServiceProvider::publishableGroups();

    expect($tags)->toContain(
        'module-generator-config',
        'module-generator-stubs',
        'module-generator-scaffold',
        'module-generator-assets',
    );
});

it('enregistre le ServiceProvider du package', function () {
    expect(app()->getProvider(ModuleGeneratorServiceProvider::class))->not->toBeNull();
});
