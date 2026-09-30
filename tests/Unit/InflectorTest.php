<?php

use Amon\ModuleGenerator\Naming\Inflector;

it('utilise le pluriel anglais par défaut', function (string $model, string $table) {
    expect((new Inflector)->table($model))->toBe($table);
})->with([
    ['Client', 'clients'],
    ['Category', 'categories'],
    ['ClientContact', 'client_contacts'],
    ['Person', 'people'],
    ['Status', 'statuses'],
]);

it('applique les règles françaises si activées', function (string $word, string $plural) {
    expect((new Inflector(french: true))->plural($word))->toBe($plural);
})->with([
    ['client', 'clients'],
    ['dossier', 'dossiers'],
    ['cheval', 'chevaux'],
    ['festival', 'festivals'],
    ['travail', 'travaux'],
    ['detail', 'details'],
    ['bureau', 'bureaux'],
    ['jeu', 'jeux'],
    ['pneu', 'pneus'],
    ['bijou', 'bijoux'],
    ['trou', 'trous'],
    ['prix', 'prix'],
    ['fils', 'fils'],
    ['nez', 'nez'],
    ['oeil', 'yeux'],
]);

it('n\'accorde que le dernier mot en français', function () {
    expect((new Inflector(french: true))->table('LigneTravail'))->toBe('ligne_travaux');
});
