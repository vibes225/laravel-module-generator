<?php

use Amon\ModuleGenerator\Http\Controllers\ApiController;
use Amon\ModuleGenerator\Http\Controllers\AssetController;
use Amon\ModuleGenerator\Http\Controllers\ToolController;
use Illuminate\Support\Facades\Route;

Route::get('assets/{file}', AssetController::class)->name('asset')->where('file', 'app\.(js|css)');
Route::get('/', [ToolController::class, 'index'])->name('index');
Route::get('create', [ToolController::class, 'create'])->name('create');

Route::prefix('api')->name('api.')->group(function () {
    Route::post('preview', [ApiController::class, 'preview'])->name('preview');
    Route::post('generate', [ApiController::class, 'generate'])->name('generate');
    Route::get('modules/{slug}/removal', [ApiController::class, 'removal'])->name('removal');
    Route::delete('modules/{slug}', [ApiController::class, 'destroy'])->name('destroy');
});
