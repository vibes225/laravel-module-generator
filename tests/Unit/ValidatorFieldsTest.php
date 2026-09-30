<?php

it('accepte une définition minimale', function () {
    expectValid(clientInput());
});

it('signale les erreurs d\'identité', function (array $overrides, string $path) {
    expectInvalid(clientInput($overrides), $path);
})->with([
    'version de schéma' => [['schema_version' => 2], 'schema_version'],
    'nature inconnue' => [['kind' => 'weird'], 'kind'],
    'modèle en minuscules' => [['model' => 'client'], 'model'],
    'modèle réservé' => [['model' => 'Class'], 'model'],
    'table invalide' => [['table' => 'Clients'], 'table'],
    'slug invalide' => [['slug' => 'Mes Clients'], 'slug'],
    'icône invalide' => [['icon' => 'Users!'], 'icon'],
    'libellé vide' => [['name' => ' '], 'name'],
    'groupe de menu' => [['menu' => ['group' => 'Mon Groupe']], 'menu.group'],
    'ordre de menu' => [['menu' => ['order' => '1']], 'menu.order'],
    'permission vide' => [['menu' => ['permission' => '']], 'menu.permission'],
    'per_page' => [['options' => ['per_page' => 0]], 'options.per_page'],
    'direction' => [['options' => ['default_sort' => ['direction' => 'up']]], 'options.default_sort.direction'],
    'tri sur colonne non triable' => [['options' => ['default_sort' => ['field' => 'nope']]], 'options.default_sort.field'],
    'aucun champ' => [['fields' => []], 'fields'],
]);

it('autorise le tri par défaut sur id et les timestamps', function () {
    expectValid(clientInput(['options' => ['default_sort' => ['field' => 'created_at', 'direction' => 'desc']]]));
    expectInvalid(clientInput(['options' => ['timestamps' => false, 'default_sort' => ['field' => 'created_at']]]), 'options.default_sort.field');
});

it('signale les erreurs de champ', function (array $field, string $path) {
    expectInvalid(clientInput(['fields' => [['name' => 'name', 'type' => 'string'], $field]]), 'fields.1.'.$path);
})->with([
    'nom invalide' => [['name' => 'First-Name', 'type' => 'string'], 'name'],
    'colonne système' => [['name' => 'created_at', 'type' => 'datetime'], 'name'],
    'type inconnu' => [['name' => 'x', 'type' => 'money'], 'type'],
    'option invalide' => [['name' => 'x', 'type' => 'string', 'options' => ['max' => -1]], 'options.max'],
    'unique interdit' => [['name' => 'x', 'type' => 'text', 'unique' => true], 'unique'],
    'tri interdit' => [['name' => 'x', 'type' => 'json', 'sortable' => true], 'sortable'],
    'recherche interdite' => [['name' => 'x', 'type' => 'integer', 'searchable' => true], 'searchable'],
    'défaut interdit' => [['name' => 'x', 'type' => 'password', 'default' => 'secret'], 'default'],
    'défaut mal typé' => [['name' => 'x', 'type' => 'integer', 'default' => 'abc'], 'default'],
    'défaut enum hors liste' => [['name' => 'x', 'type' => 'enum', 'default' => 'lost', 'options' => ['values' => ['draft']]], 'default'],
    'drapeau non booléen' => [['name' => 'x', 'type' => 'string', 'nullable' => 'yes'], 'nullable'],
    'foreignId sans table' => [['name' => 'user_id', 'type' => 'foreignId'], 'options.references'],
]);

it('détecte les noms de champs en double', function () {
    expectInvalid(clientInput(['fields' => [['name' => 'name', 'type' => 'string'], ['name' => 'name', 'type' => 'text']]]), 'fields.1.name');
});

it('accepte chaque type avec ses options par défaut', function (string $type) {
    $options = match ($type) {
        'enum' => ['values' => ['a', 'b']],
        'foreignId' => ['references' => 'users'],
        default => [],
    };

    expectValid(clientInput(['fields' => [['name' => 'name', 'type' => 'string'], ['name' => 'value', 'type' => $type, 'options' => $options]]]));
})->with(['string', 'text', 'integer', 'bigInteger', 'decimal', 'boolean', 'date', 'datetime', 'time', 'email',
    'password', 'enum', 'json', 'foreignId', 'uuid', 'file', 'image']);
