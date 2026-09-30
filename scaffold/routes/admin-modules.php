<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Modules d'administration
|--------------------------------------------------------------------------
| Chaque module possède son fichier routes/modules/<slug>.php.
| Ce fichier est requis depuis routes/web.php (groupe `web`) : préfixe admin,
| noms admin., utilisateurs authentifiés.
*/

Route::middleware(['web', 'auth'])->prefix('admin')->name('admin.')->group(function () {
    foreach (glob(__DIR__.'/modules/*.php') ?: [] as $module) {
        require $module;
    }
});
