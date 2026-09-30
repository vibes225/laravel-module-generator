<?php

use App\Http\Controllers\Admin\CategoryController;
use Illuminate\Support\Facades\Route;

Route::resource('categories', CategoryController::class)
    ->parameters(['categories' => 'category']);
