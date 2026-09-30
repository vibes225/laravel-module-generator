<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Interface web de l'outil
    |--------------------------------------------------------------------------
    | Les routes de l'interface ne sont enregistrées que si `enabled` est vrai
    | (par défaut : uniquement en environnement local).
    */
    'ui' => [
        'enabled' => env('MODULE_GENERATOR_UI', env('APP_ENV') === 'local'),
        'prefix' => 'module-generator',
        'middleware' => ['web'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Convention de dossiers du frontend généré
    |--------------------------------------------------------------------------
    | `breeze`      : resources/js/Pages/Clients/Index.jsx (défaut)
    | `starter-kit` : resources/js/pages/clients/index.jsx
    */
    'convention' => 'breeze',

    /*
    |--------------------------------------------------------------------------
    | Nommage
    |--------------------------------------------------------------------------
    | Pluralisation française pour les noms de table et de slug dérivés.
    | Ces noms restent modifiables dans la définition du module.
    */
    'naming' => [
        'french_plural' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Formatage du code généré
    |--------------------------------------------------------------------------
    | Les empreintes du manifest sont calculées après formatage.
    */
    'format' => [
        'pint' => true,
        'prettier' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Types de champs
    |--------------------------------------------------------------------------
    | Registre extensible : ajouter ici une classe implémentant le contrat
    | FieldType pour déclarer un type supplémentaire.
    */
    'field_types' => [],

    /*
    |--------------------------------------------------------------------------
    | Stubs
    |--------------------------------------------------------------------------
    | Les stubs publiés dans le projet (tag `module-generator-stubs`) priment
    | sur ceux du package.
    */
    'stubs_path' => base_path('stubs/module-generator'),

    /*
    |--------------------------------------------------------------------------
    | Registre des modules
    |--------------------------------------------------------------------------
    | Manifests versionnés dans git (un fichier JSON par module).
    */
    'manifest_path' => base_path('.module-generator'),

];
