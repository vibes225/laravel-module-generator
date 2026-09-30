<?php

use Amon\ModuleGenerator\Console\CliDefinitionParser;

it('lit les champs, options de type et modificateurs', function (string $spec, array $expected) {
    expect((new CliDefinitionParser)->field($spec))->toBe($expected);
})->with([
    ['name:string', ['name' => 'name', 'type' => 'string']],
    ['email:email:unique', ['name' => 'email', 'type' => 'email', 'unique' => true]],
    ['title:string(100):searchable:hidden', ['name' => 'title', 'type' => 'string', 'options' => ['max' => 100], 'searchable' => true, 'in_table' => false]],
    ['status:enum(draft,sent):default=draft', ['name' => 'status', 'type' => 'enum', 'options' => ['values' => ['draft', 'sent']], 'default' => 'draft']],
    ['price:decimal(12,2):nullable', ['name' => 'price', 'type' => 'decimal', 'options' => ['precision' => 12, 'scale' => 2], 'nullable' => true]],
    ['user_id:foreignId(users,cascade)', ['name' => 'user_id', 'type' => 'foreignId', 'options' => ['references' => 'users', 'on_delete' => 'cascade']]],
    ['active:boolean:default=true', ['name' => 'active', 'type' => 'boolean', 'default' => true]],
    ['stock:integer(0,500):default=10', ['name' => 'stock', 'type' => 'integer', 'options' => ['min' => 0, 'max' => 500], 'default' => 10]],
    ['cv:file(pdf,docx):noform:nodetail', ['name' => 'cv', 'type' => 'file', 'options' => ['extensions' => ['pdf', 'docx']], 'in_form' => false, 'in_detail' => false]],
    ['name:string:label=Nom complet', ['name' => 'name', 'type' => 'string', 'label' => 'Nom complet']],
]);

it('lit les relations', function (string $spec, array $expected) {
    expect((new CliDefinitionParser)->relation($spec))->toBe($expected);
})->with([
    ['belongsTo:Client', ['type' => 'belongsTo', 'target' => 'Client']],
    ['belongsTo:User:owner:nullable:display=email', ['type' => 'belongsTo', 'target' => 'User', 'name' => 'owner', 'nullable' => true, 'display' => 'email']],
    ['hasMany:InvoiceLine:lines', ['type' => 'hasMany', 'target' => 'InvoiceLine', 'name' => 'lines']],
    ['belongsToMany:Tag:pivot=generate:timestamps:form', ['type' => 'belongsToMany', 'target' => 'Tag', 'pivot' => ['mode' => 'generate', 'timestamps' => true], 'in_form' => true]],
    ['belongsToMany:Tag:pivot=module=client-tag', ['type' => 'belongsToMany', 'target' => 'Tag', 'pivot' => ['mode' => 'module', 'module' => 'client-tag']]],
]);

it('refuse les spécifications invalides', function (string $method, string $spec) {
    (new CliDefinitionParser)->{$method}($spec);
})->throws(InvalidArgumentException::class)->with([
    ['field', 'name'],
    ['field', 'name:string:bogus'],
    ['field', 'flag:boolean(1)'],
    ['field', 'title:string(abc)'],
    ['relation', 'belongsTo'],
    ['relation', 'belongsTo:Client:owner:weird'],
]);

it('découpe les listes', function () {
    expect((new CliDefinitionParser)->list(' Client, Dossier ,'))->toBe(['Client', 'Dossier'])
        ->and((new CliDefinitionParser)->list(null))->toBe([]);
});
