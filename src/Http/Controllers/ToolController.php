<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Http\Controllers;

use Amon\ModuleGenerator\Fields\FieldTypeRegistry;
use Amon\ModuleGenerator\Manifest\ManifestRepository;
use Amon\ModuleGenerator\Registry\ModuleRegistry;
use Inertia\Inertia;
use Inertia\Response;

final class ToolController
{
    public function index(ManifestRepository $manifests): Response
    {
        return Inertia::render('Modules', [
            'modules' => array_map(fn ($manifest) => [
                'slug' => $manifest->slug,
                'name' => $manifest->definition['name'] ?? $manifest->slug,
                'model' => $manifest->definition['model'] ?? null,
                'kind' => $manifest->definition['kind'] ?? 'standard',
                'complete' => $manifest->isComplete(),
                'files' => count($manifest->files),
                'generated_at' => $manifest->generatedAt,
            ], $manifests->all()),
            'archived' => array_map(fn (string $path) => basename($path, '.json'), $manifests->archived()),
        ]);
    }

    public function create(FieldTypeRegistry $types, ModuleRegistry $registry): Response
    {
        return Inertia::render('Builder', [
            'types' => $types->describe(),
            'models' => $registry->models(),
            'icons' => ['bar-chart', 'bell', 'building', 'calendar', 'credit-card', 'dashboard', 'file-text', 'folder', 'home', 'inbox', 'layers', 'mail', 'package', 'settings', 'shopping-cart', 'star', 'tag', 'users'],
        ]);
    }
}
