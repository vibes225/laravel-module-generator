<?php

use App\Http\Controllers\Admin\TaggableController;
use Illuminate\Support\Facades\Route;

Route::resource('taggables', TaggableController::class)
    ->parameters(['taggables' => 'taggable']);
