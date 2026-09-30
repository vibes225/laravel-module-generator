<?php

function withRelation(array $relation): array
{
    return clientInput(['singular' => 'Invoice', 'relations' => [$relation]]);
}

function pivotInput(array $pivot = [], array $overrides = []): array
{
    return array_replace([
        'kind' => 'pivot',
        'singular' => 'Client tag',
        'pivot' => array_replace(['left' => ['model' => 'Client'], 'right' => ['model' => 'Tag']], $pivot),
    ], $overrides);
}

it('accepte les quatre types de relations', function () {
    expectValid(clientInput(['singular' => 'Invoice', 'relations' => [
        ['type' => 'belongsTo', 'target' => 'Client'],
        ['type' => 'hasOne', 'target' => 'Receipt'],
        ['type' => 'hasMany', 'target' => 'InvoiceLine'],
        ['type' => 'belongsToMany', 'target' => 'Tag'],
        ['type' => 'belongsToMany', 'target' => 'Label', 'pivot' => ['mode' => 'module', 'module' => 'invoice-labels', 'model' => 'InvoiceLabel', 'table' => 'invoice_label']],
    ]]));
});

it('signale les erreurs de relation', function (array $relation, string $path) {
    expectInvalid(withRelation($relation), 'relations.0.'.$path);
})->with([
    'type inconnu' => [['type' => 'morphMany', 'target' => 'Comment'], 'type'],
    'cible invalide' => [['type' => 'belongsTo', 'target' => 'client'], 'target'],
    'nom invalide' => [['type' => 'belongsTo', 'target' => 'Client', 'name' => 'the_client'], 'name'],
    'méthode Eloquent' => [['type' => 'belongsTo', 'target' => 'Client', 'name' => 'save'], 'name'],
    'on_delete inconnu' => [['type' => 'belongsTo', 'target' => 'Client', 'on_delete' => 'wipe'], 'on_delete'],
    'null sans nullable' => [['type' => 'belongsTo', 'target' => 'Client', 'on_delete' => 'null'], 'on_delete'],
    'formulaire sur hasMany' => [['type' => 'hasMany', 'target' => 'Line', 'in_form' => true], 'in_form'],
]);

function withPivot(array $pivot, array $relation = []): array
{
    return withRelation(array_replace(['type' => 'belongsToMany', 'target' => 'Tag', 'pivot' => $pivot], $relation));
}

it('signale les erreurs de pivot de relation', function (array $input, string $path) {
    expectInvalid($input, 'relations.0.'.$path);
})->with([
    'mode inconnu' => fn () => [withPivot(['mode' => 'auto']), 'pivot.mode'],
    'champs sans table' => fn () => [withPivot(['fields' => [['name' => 'role', 'type' => 'string']]]), 'pivot.mode'],
    'module manquant' => fn () => [withPivot(['mode' => 'module']), 'pivot.module'],
    'module non résolu' => fn () => [withPivot(['mode' => 'module', 'module' => 'invoice-tags']), 'pivot.module'],
    'table du module' => fn () => [withPivot(['mode' => 'generate', 'table' => 'invoices']), 'pivot.table'],
    'clés identiques' => fn () => [withPivot([], ['target' => 'Invoice', 'related_pivot_key' => 'invoice_id']), 'related_pivot_key'],
    'password' => fn () => [withPivot(['mode' => 'generate', 'fields' => [['name' => 'secret', 'type' => 'password']]]), 'pivot.fields.0.type'],
    'colonne en double' => fn () => [withPivot(['mode' => 'generate', 'fields' => [['name' => 'tag_id', 'type' => 'integer']]]), 'pivot.fields.0.name'],
]);

it('détecte les collisions de colonnes et de méthodes', function () {
    expectInvalid(clientInput([
        'singular' => 'Invoice',
        'fields' => [['name' => 'client_id', 'type' => 'integer']],
        'relations' => [['type' => 'belongsTo', 'target' => 'Client']],
    ]), 'relations.0.foreign_key');

    expectInvalid(clientInput(['relations' => [
        ['type' => 'belongsTo', 'target' => 'User'],
        ['type' => 'hasOne', 'target' => 'User'],
    ]]), 'relations.1.name');
});

it('valide la structure en arbre', function () {
    expectValid(clientInput(['tree' => true]));
    expectInvalid(clientInput(['tree' => true, 'fields' => [['name' => 'parent_id', 'type' => 'integer']]]), 'fields.0.name');
    expectInvalid(clientInput(['tree' => true, 'relations' => [['type' => 'hasMany', 'target' => 'Client', 'name' => 'children']]]), 'relations.0.name');
    expectInvalid(pivotInput(overrides: ['tree' => true]), 'tree');
});

it('valide l\'option polymorphe', function () {
    expectValid(clientInput(['morph' => ['name' => 'commentable', 'types' => []]]));
    expectValid(clientInput(['tree' => true, 'morph' => ['name' => 'commentable', 'key_type' => 'uuid', 'types' => [['model' => 'Client']]]]));
    expectInvalid(clientInput(['morph' => ['name' => 'Commentable']]), 'morph.name');
    expectInvalid(clientInput(['morph' => ['name' => 'commentable', 'key_type' => 'string']]), 'morph.key_type');
    expectInvalid(clientInput(['morph' => ['name' => 'commentable', 'types' => [['model' => 'Client'], ['model' => 'Client']]]]), 'morph.types.1.model');
    expectInvalid(clientInput(['morph' => ['name' => 'commentable', 'types' => [['model' => 'Client', 'class' => 'not a class']]]]), 'morph.types.0.class');
    expectInvalid(clientInput(['morph' => ['name' => 'commentable'], 'fields' => [['name' => 'commentable_id', 'type' => 'integer']]]), 'fields.0.name');
});

it('valide un module pivot', function () {
    $morph = ['morph' => ['name' => 'taggable', 'types' => [['model' => 'Client']]]];

    expectValid(pivotInput());
    expectValid(pivotInput(['right' => ['polymorphic' => true]], $morph));
    expectInvalid(pivotInput(['left' => ['model' => 'client']]), 'pivot.left.model');
    expectInvalid(pivotInput(['left' => ['model' => 'Client', 'on_delete' => 'null']]), 'pivot.left.on_delete');
    expectInvalid(pivotInput(['right' => ['model' => 'Tag', 'foreign_key' => 'client_id']]), 'pivot.right.foreign_key');
    expectInvalid(pivotInput(['left' => ['polymorphic' => true], 'right' => ['polymorphic' => true]], $morph), 'pivot');
    expectInvalid(pivotInput(['right' => ['polymorphic' => true]]), 'morph');
    expectInvalid(pivotInput(['right' => ['polymorphic' => true], 'primary_id' => false], $morph), 'pivot.primary_id');
    expectInvalid(pivotInput(overrides: $morph), 'morph');
    expectInvalid(pivotInput(overrides: ['fields' => [['name' => 'tag_id', 'type' => 'integer']]]), 'fields.0.name');
    expectInvalid(clientInput(['pivot' => ['left' => []]]), 'pivot');
});

it('accepte un pivot auto-référent avec des clés distinctes', function () {
    expectValid(pivotInput([
        'left' => ['model' => 'User', 'foreign_key' => 'follower_id'],
        'right' => ['model' => 'User', 'foreign_key' => 'followed_id'],
    ]));
});

it('autorise la sélection multiple belongsToMany sans champs pivot', function () {
    expectValid(withPivot(['mode' => 'none'], ['in_form' => true]));
    expectValid(withPivot(['mode' => 'generate', 'timestamps' => true], ['in_form' => true, 'display' => 'label']));
    expectValid(withPivot(['mode' => 'module', 'module' => 'invoice-tags', 'model' => 'InvoiceTag', 'table' => 'invoice_tag'], ['in_form' => true]));
    expectInvalid(withPivot(['mode' => 'generate', 'fields' => [['name' => 'role', 'type' => 'string']]], ['in_form' => true]), 'relations.0.in_form');
    expectInvalid(withPivot(['mode' => 'none'], ['in_form' => true, 'display' => 'Label']), 'relations.0.display');
});

it('laisse la sélection multiple désactivée par défaut', function () {
    $relation = normalizeDefinition(withPivot(['mode' => 'none']))['relations'][0];

    expect($relation['in_form'])->toBeFalse()->and($relation['display'])->toBe('name');
});
