<?php

use App\Http\Controllers\Admin\ClientTagController;
use Illuminate\Support\Facades\Route;

Route::resource('client-tag', ClientTagController::class)
    ->parameters(['client-tag' => 'client_tag']);
