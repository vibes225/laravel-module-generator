<?php

it('dérive modèle, table, slug et libellés depuis le libellé singulier', function () {
    $d = normalizeDefinition(['singular' => 'Client contact', 'fields' => [['name' => 'name', 'type' => 'string']]]);

    expect($d)->toMatchArray([
        'schema_version' => 1,
        'kind' => 'standard',
        'model' => 'ClientContact',
        'table' => 'client_contacts',
        'slug' => 'client-contacts',
        'name' => 'Client contacts',
        'icon' => 'folder',
        'tree' => false,
        'morph' => null,
        'pivot' => null,
    ]);
});

it('translittère les accents pour le modèle', function () {
    expect(normalizeDefinition(['singular' => 'Catégorie'])['model'])->toBe('Categorie');
});

it('utilise le pluriel français si activé, noms toujours modifiables', function () {
    $fr = normalizeDefinition(['singular' => 'Travail'], french: true);
    expect($fr['table'])->toBe('travaux')->and($fr['name'])->toBe('Travaux');

    $custom = normalizeDefinition(['singular' => 'Client', 'table' => 'crm_clients', 'slug' => 'crm']);
    expect($custom['table'])->toBe('crm_clients')->and($custom['slug'])->toBe('crm');
});

it('applique les valeurs par défaut du menu et des options', function () {
    $d = normalizeDefinition(clientInput());

    expect($d['menu'])->toBe(['enabled' => true, 'group' => 'main', 'order' => 100, 'permission' => null])
        ->and($d['options'])->toBe([
            'timestamps' => true,
            'soft_deletes' => false,
            'per_page' => 15,
            'default_sort' => ['field' => 'name', 'direction' => 'asc'],
        ]);
});

it('complète un champ avec les défauts de son type', function () {
    $field = normalizeDefinition(clientInput(['fields' => [['name' => 'first_name', 'type' => 'string']]]))['fields'][0];

    expect($field)->toBe([
        'name' => 'first_name',
        'label' => 'First name',
        'type' => 'string',
        'nullable' => false,
        'unique' => false,
        'default' => null,
        'index' => false,
        'options' => ['max' => 255],
        'searchable' => true,
        'sortable' => true,
        'filterable' => false,
        'in_table' => true,
        'in_form' => true,
        'in_detail' => true,
    ]);
});

it('masque password de la table et de la fiche', function () {
    $field = normalizeDefinition(clientInput(['fields' => [['name' => 'secret', 'type' => 'password']]]))['fields'][0];

    expect($field['in_table'])->toBeFalse()->and($field['in_detail'])->toBeFalse()->and($field['in_form'])->toBeTrue();
});

it('dérive les relations', function () {
    $relations = normalizeDefinition(clientInput(['singular' => 'Invoice', 'relations' => [
        ['type' => 'belongsTo', 'target' => 'ClientContact'],
        ['type' => 'hasMany', 'target' => 'InvoiceLine'],
        ['type' => 'belongsToMany', 'target' => 'Tag', 'pivot' => ['mode' => 'generate']],
        ['type' => 'belongsTo', 'target' => 'User', 'name' => 'owner', 'nullable' => true],
    ]]))['relations'];

    expect($relations[0])->toMatchArray([
        'name' => 'clientContact', 'label' => 'Client contact', 'target_table' => 'client_contacts',
        'foreign_key' => 'client_contact_id', 'on_delete' => 'restrict', 'display' => 'name',
        'in_table' => true, 'in_form' => true, 'pivot' => null,
    ])
        ->and($relations[1])->toMatchArray(['name' => 'invoiceLines', 'foreign_key' => 'invoice_id', 'in_form' => false])
        ->and($relations[2])->toMatchArray([
            'name' => 'tags', 'foreign_pivot_key' => 'invoice_id', 'related_pivot_key' => 'tag_id',
            'pivot' => ['mode' => 'generate', 'table' => 'invoice_tag', 'timestamps' => false, 'fields' => [], 'module' => null, 'model' => null],
        ])
        ->and($relations[3])->toMatchArray(['foreign_key' => 'owner_id', 'on_delete' => 'null']);
});

it('retire les drapeaux de présentation des champs pivot', function () {
    $pivot = normalizeDefinition(clientInput(['relations' => [
        ['type' => 'belongsToMany', 'target' => 'Tag', 'pivot' => ['mode' => 'generate', 'fields' => [['name' => 'role', 'type' => 'string']]]],
    ]]))['relations'][0]['pivot'];

    expect($pivot['fields'][0])->toMatchArray(['searchable' => false, 'sortable' => false, 'in_table' => false, 'in_form' => false]);
});

it('normalise un module pivot et nomme sa table par ordre alphabétique', function () {
    $d = normalizeDefinition([
        'kind' => 'pivot',
        'singular' => 'Tag assignment',
        'model' => 'TagAssignment',
        'pivot' => ['left' => ['model' => 'Tag'], 'right' => ['model' => 'Client']],
    ]);

    expect($d['table'])->toBe('client_tag')
        ->and($d['pivot'])->toBe([
            'left' => ['model' => 'Tag', 'table' => 'tags', 'foreign_key' => 'tag_id', 'on_delete' => 'cascade', 'polymorphic' => false, 'display' => 'name'],
            'right' => ['model' => 'Client', 'table' => 'clients', 'foreign_key' => 'client_id', 'on_delete' => 'cascade', 'polymorphic' => false, 'display' => 'name'],
            'unique_pair' => true,
            'primary_id' => true,
        ]);
});

it('normalise les options polymorphes avec l\'espace de noms des modèles', function () {
    $morph = normalizeDefinition(clientInput(['morph' => ['name' => 'commentable', 'types' => [['model' => 'Client']]]]))['morph'];

    expect($morph)->toBe([
        'name' => 'commentable',
        'key_type' => 'int',
        'nullable' => false,
        'types' => [['model' => 'Client', 'class' => 'App\Models\Client', 'label' => 'Client', 'display_column' => 'name']],
    ]);
});

it('est idempotent', function () {
    $input = clientInput([
        'relations' => [['type' => 'belongsToMany', 'target' => 'Tag', 'pivot' => [
            'mode' => 'generate',
            'fields' => [['name' => 'role', 'type' => 'string']],
        ]]],
        'morph' => ['name' => 'commentable', 'types' => [['model' => 'Client']]],
        'tree' => true,
    ]);
    $once = normalizeDefinition($input);

    expect(normalizeDefinition($once))->toBe($once);
});

it('ne plante pas sur une entrée malformée', function () {
    $d = normalizeDefinition(['fields' => 'nope', 'relations' => [42], 'menu' => 'x', 'morph' => 'y']);

    expect($d['fields'])->toBe([])->and($d['relations'])->toBe([42])->and($d['morph'])->toBe('y');
});
