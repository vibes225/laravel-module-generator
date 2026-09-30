<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

/** Inertia de l'outil : root view et version propres, indépendants du Vite de l'hôte. */
final class HandleToolRequests extends Middleware
{
    protected $rootView = 'module-generator::app';

    public function version(Request $request): ?string
    {
        $bundle = dirname(__DIR__, 3).'/dist/app.js';

        return is_file($bundle) ? (string) md5_file($bundle) : null;
    }

    /** @return array<string, mixed> */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'tool' => [
                'prefix' => config('module-generator.ui.prefix', 'module-generator'),
                'convention' => config('module-generator.convention', 'breeze'),
            ],
        ];
    }
}
