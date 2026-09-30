<?php

use Amon\ModuleGenerator\Definition\InvalidDefinition;
use Amon\ModuleGenerator\Definition\KeyType;
use Amon\ModuleGenerator\Definition\ModuleDefinition;
use Amon\ModuleGenerator\Definition\ModuleKind;
use Amon\ModuleGenerator\Definition\PivotMode;
use Amon\ModuleGenerator\Definition\RelationType;

function fullInput(): array
{
    return [
        'singular' => 'Invoice',
        'icon' => 'file-text',
        'menu' => ['group' => 'billing', 'order' => 10, 'permission' => 'manage-invoices'],
        'options' => ['soft_deletes' => true, 'per_page' => 25, 'default_sort' => ['field' => 'issued_at', 'direction' => 'desc']],
        'fields' => [
            ['name' => 'number', 'type' => 'string', 'unique' => true],
            ['name' => 'status', 'type' => 'enum', 'default' => 'draft', 'options' => ['values' => ['draft', 'sent', 'paid']]],
            ['name' => 'amount', 'type' => 'decimal', 'options' => ['precision' => 12, 'scale' => 2]],
            ['name' => 'issued_at', 'type' => 'date', 'sortable' => true],
            ['name' => 'attachment', 'type' => 'file', 'nullable' => true],
        ],
        'relations' => [
            ['type' => 'belongsTo', 'target' => 'Client'],
            ['type' => 'hasMany', 'target' => 'InvoiceLine', 'name' => 'lines'],
            ['type' => 'belongsToMany', 'target' => 'Tag', 'pivot' => [
                'mode' => 'generate', 'timestamps' => true, 'fields' => [['name' => 'weight', 'type' => 'integer']],
            ]],
        ],
        'tree' => true,
        'morph' => ['name' => 'billable', 'key_type' => 'ulid', 'nullable' => true, 'types' => [
            ['model' => 'Client'],
            ['model' => 'Supplier', 'display_column' => 'company'],
        ]],
    ];
}

it('construit une définition immuable complète', function () {
    $d = definitionFactory()->make(fullInput());

    expect($d)->toBeInstanceOf(ModuleDefinition::class)
        ->and($d->kind)->toBe(ModuleKind::Standard)
        ->and($d->table)->toBe('invoices')
        ->and($d->fields)->toHaveCount(5)
        ->and($d->field('status')->options['values'][0])->toBe(['value' => 'draft', 'label' => 'Draft'])
        ->and($d->relations[0]->type)->toBe(RelationType::BelongsTo)
        ->and($d->relations[2]->pivot->mode)->toBe(PivotMode::Generate)
        ->and($d->relations[2]->pivot->fields[0]->name)->toBe('weight')
        ->and($d->morph->keyType)->toBe(KeyType::Ulid)
        ->and($d->morph->types[1]->class)->toBe('App\Models\Supplier')
        ->and($d->menu->permission)->toBe('manage-invoices')
        ->and($d->options->sortDirection)->toBe('desc')
        ->and($d->tree)->toBeTrue();
});

it('sérialise sans perte (forme archivée du manifest)', function () {
    $factory = definitionFactory();
    $array = $factory->make(fullInput())->toArray();

    expect($array)->toBe(normalizeDefinition(fullInput()))
        ->and($factory->make($array)->toArray())->toBe($array)
        ->and($factory->fromJson(json_encode($array))->toArray())->toBe($array);
});

it('construit un module pivot', function () {
    $d = definitionFactory()->make([
        'kind' => 'pivot',
        'singular' => 'Client tag',
        'pivot' => ['left' => ['model' => 'Client'], 'right' => ['model' => 'Tag'], 'primary_id' => false],
        'fields' => [['name' => 'note', 'type' => 'string', 'nullable' => true]],
    ]);

    expect($d->isPivot())->toBeTrue()
        ->and($d->table)->toBe('client_tag')
        ->and($d->pivot->left->foreignKey)->toBe('client_id')
        ->and($d->pivot->primaryId)->toBeFalse()
        ->and($d->pivot->uniquePair)->toBeTrue();
});

it('lève InvalidDefinition avec les erreurs par chemin', function () {
    try {
        definitionFactory()->make(['singular' => 'Client', 'fields' => [['name' => 'Bad Name', 'type' => 'nope']]]);
        $this->fail('Exception attendue');
    } catch (InvalidDefinition $e) {
        expect($e->errors)->toHaveKeys(['fields.0.name', 'fields.0.type'])
            ->and($e->getMessage())->toContain('fields.0.name');
    }
});

it('refuse un JSON invalide', function () {
    definitionFactory()->fromJson('{oops');
})->throws(InvalidDefinition::class);
