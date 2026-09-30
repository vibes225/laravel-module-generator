<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Sert l'interface précompilée (dist/) sans passer par le Vite de l'hôte. */
final class AssetController
{
    public function __invoke(string $file): BinaryFileResponse
    {
        $path = dirname(__DIR__, 3).'/dist/'.$file;
        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Content-Type' => str_ends_with($file, '.css') ? 'text/css; charset=utf-8' : 'application/javascript; charset=utf-8',
            'Cache-Control' => 'no-cache',
        ]);
    }
}
