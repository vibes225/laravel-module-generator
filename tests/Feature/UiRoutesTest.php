<?php

use Illuminate\Support\Facades\Route;

it('n\'enregistre pas l\'interface hors environnement local', function () {
    expect(app()->environment())->not->toBe('local')
        ->and(Route::has('module-generator.index'))->toBeFalse();
});
