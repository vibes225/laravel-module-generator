<?php

use Amon\ModuleGenerator\Definition\DefinitionFactory;
use Amon\ModuleGenerator\Fields\FieldTypeRegistry;
use Amon\ModuleGenerator\Tests\Fixtures\ColorType;

it('résout la fabrique depuis le conteneur avec le pluriel anglais par défaut', function () {
    $definition = app(DefinitionFactory::class)->make(['singular' => 'Category', 'fields' => [['name' => 'name', 'type' => 'string']]]);

    expect($definition->table)->toBe('categories')->and($definition->slug)->toBe('categories');
});

it('applique le pluriel français et l\'espace de noms configurés', function () {
    config(['module-generator.naming.french_plural' => true, 'module-generator.naming.models_namespace' => 'Domain\Models']);

    $definition = app(DefinitionFactory::class)->make([
        'singular' => 'Bureau',
        'fields' => [['name' => 'name', 'type' => 'string']],
        'morph' => ['name' => 'owner', 'types' => [['model' => 'Agent']]],
    ]);

    expect($definition->table)->toBe('bureaux')->and($definition->morph->types[0]->class)->toBe('Domain\Models\Agent');
});

it('accepte un type de champ déclaré dans la config', function () {
    config(['module-generator.field_types' => [ColorType::class]]);
    app()->forgetInstance(FieldTypeRegistry::class);

    expect(app(FieldTypeRegistry::class)->has('color'))->toBeTrue();

    $definition = app(DefinitionFactory::class)->make([
        'singular' => 'Tag',
        'fields' => [['name' => 'color', 'type' => 'color', 'default' => '#ff0000']],
    ]);

    expect($definition->field('color')->type)->toBe('color');
});
