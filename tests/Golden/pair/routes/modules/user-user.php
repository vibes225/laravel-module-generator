<?php

use App\Http\Controllers\Admin\FollowController;
use Illuminate\Support\Facades\Route;

Route::controller(FollowController::class)->prefix('user-user')->name('user-user.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('create', 'create')->name('create');
    Route::post('/', 'store')->name('store');
    Route::delete('{follower_id}/{followed_id}', 'destroy')->name('destroy');
});
