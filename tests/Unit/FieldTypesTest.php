<?php

use Amon\ModuleGenerator\Contracts\FieldType;
use Amon\ModuleGenerator\Fields\FieldTypeRegistry;
use Amon\ModuleGenerator\Fields\Types\StringType;

it('enregistre les 17 types prévus', function () {
    expect((new FieldTypeRegistry)->names())->toEqualCanonicalizing([
        'string', 'text', 'integer', 'bigInteger', 'decimal', 'boolean', 'date', 'datetime', 'time', 'email',
        'password', 'enum', 'json', 'foreignId', 'uuid', 'file', 'image',
    ]);
});

it('décrit chaque type pour l\'UI', function () {
    foreach ((new FieldTypeRegistry)->describe() as $type) {
        expect($type)->toHaveKeys(['name', 'label', 'options', 'allows', 'defaults', 'pivot'])
            ->and($type['defaults'])->toHaveKeys(['searchable', 'sortable', 'filterable', 'in_table', 'in_form', 'in_detail']);

        foreach ($type['options'] as $option) {
            expect($option)->toHaveKeys(['type', 'label']);
        }
    }
});

it('complète les options par défaut', function (string $type, array $expected) {
    expect((new FieldTypeRegistry)->get($type)->normalizeOptions([]))->toMatchArray($expected);
})->with([
    ['string', ['max' => 255]],
    ['decimal', ['precision' => 10, 'scale' => 2]],
    ['password', ['min' => 8]],
    ['file', ['disk' => 'public', 'max_size' => 2048]],
    ['image', ['extensions' => ['jpg', 'jpeg', 'png', 'webp']]],
    ['foreignId', ['on_delete' => 'restrict']],
]);

it('rejette les options invalides', function (string $type, array $options, string $key) {
    $registry = new FieldTypeRegistry;
    $fieldType = $registry->get($type);

    expect($fieldType->validateOptions($fieldType->normalizeOptions($options)))->toHaveKey($key);
})->with([
    'max négatif' => ['string', ['max' => 0], 'max'],
    'option inconnue' => ['string', ['foo' => 1], 'foo'],
    'décimales > précision' => ['decimal', ['precision' => 4, 'scale' => 6], 'scale'],
    'disque invalide' => ['file', ['disk' => 'Mon Disque'], 'disk'],
    'extensions avec point' => ['image', ['extensions' => ['.png']], 'extensions'],
    'on_delete inconnu' => ['foreignId', ['references' => 'users', 'on_delete' => 'wipe'], 'on_delete'],
    'references manquante' => ['foreignId', [], 'references'],
    'enum vide' => ['enum', ['values' => []], 'values'],
    'enum sans valeurs' => ['enum', [], 'values'],
    'enum valeur invalide' => ['enum', ['values' => ['Draft']], 'values'],
    'enum doublon' => ['enum', ['values' => ['draft', 'draft']], 'values'],
]);

it('normalise les valeurs d\'enum en couples valeur/libellé', function () {
    $options = (new FieldTypeRegistry)->get('enum')->normalizeOptions(['values' => ['draft', ['value' => 'sent', 'label' => 'Envoyé']]]);

    expect($options['values'])->toBe([
        ['value' => 'draft', 'label' => 'Draft'],
        ['value' => 'sent', 'label' => 'Envoyé'],
    ]);
});

it('valide les valeurs par défaut selon le type', function (string $type, mixed $value, bool $valid, array $options = []) {
    $fieldType = (new FieldTypeRegistry)->get($type);
    $error = $fieldType->validateDefault($value, $fieldType->normalizeOptions($options));

    expect($error === null)->toBe($valid);
})->with([
    ['string', 'abc', true],
    ['string', 12, false],
    ['integer', 3, true],
    ['integer', '3', false],
    ['decimal', 1.5, true],
    ['boolean', false, true],
    ['boolean', 0, false],
    ['date', '2026-02-28', true],
    ['date', '2026-02-30', false],
    ['datetime', '2026-01-01 10:00:00', true],
    ['time', '09:30', true],
    ['time', '25:00', false],
    ['enum', 'sent', true, ['values' => ['draft', 'sent']]],
    ['enum', 'lost', false, ['values' => ['draft', 'sent']]],
    ['json', '{}', false],
]);

it('exclut password, file et image des tables pivot', function () {
    $registry = new FieldTypeRegistry;

    expect($registry->get('password')->allowedInPivot())->toBeFalse()
        ->and($registry->get('file')->allowedInPivot())->toBeFalse()
        ->and($registry->get('image')->allowedInPivot())->toBeFalse()
        ->and($registry->get('string')->allowedInPivot())->toBeTrue();
});

it('accepte des types supplémentaires et refuse les classes invalides', function () {
    $registry = FieldTypeRegistry::fromClasses([StringType::class]);
    expect($registry->has('string'))->toBeTrue();

    FieldTypeRegistry::fromClasses([stdClass::class]);
})->throws(InvalidArgumentException::class);

it('implémente le contrat pour chaque type', function () {
    foreach ((new FieldTypeRegistry)->all() as $type) {
        expect($type)->toBeInstanceOf(FieldType::class);
    }
});
