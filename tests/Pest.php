<?php

use Amon\ModuleGenerator\Definition\DefinitionFactory;
use Amon\ModuleGenerator\Definition\DefinitionNormalizer;
use Amon\ModuleGenerator\Definition\DefinitionValidator;
use Amon\ModuleGenerator\Fields\FieldTypeRegistry;
use Amon\ModuleGenerator\Naming\Inflector;
use Amon\ModuleGenerator\Tests\TestCase;

uses(TestCase::class)->in('Feature');

function definitionFactory(bool $french = false): DefinitionFactory
{
    $types = new FieldTypeRegistry;

    return new DefinitionFactory(
        new DefinitionNormalizer($types, new Inflector($french)),
        new DefinitionValidator($types),
    );
}

function normalizeDefinition(array $input, bool $french = false): array
{
    return (new DefinitionNormalizer(new FieldTypeRegistry, new Inflector($french)))->normalize($input);
}

/** Erreurs de validation d'une entrée brute (après normalisation). */
function definitionErrors(array $input): array
{
    return definitionFactory()->errors($input);
}

/** Définition minimale valide, complétée par $overrides. */
function clientInput(array $overrides = []): array
{
    return array_replace([
        'singular' => 'Client',
        'fields' => [
            ['name' => 'name', 'type' => 'string'],
        ],
    ], $overrides);
}

function expectInvalid(array $input, string $path): void
{
    $errors = definitionErrors($input);

    expect($errors)->toHaveKey($path, message: 'Erreur attendue sur '.$path.', obtenu : '.json_encode($errors, JSON_UNESCAPED_UNICODE));
}

function expectValid(array $input): void
{
    expect(definitionErrors($input))->toBe([]);
}
