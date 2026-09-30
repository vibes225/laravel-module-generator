<?php

use App\Http\Controllers\Admin\ClientController;
use Illuminate\Support\Facades\Route;

Route::resource('clients', ClientController::class)
    ->parameters(['clients' => 'client']);
