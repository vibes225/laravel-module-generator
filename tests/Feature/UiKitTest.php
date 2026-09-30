<?php

// Le kit UI publié dans l'hôte doit être autonome : imports relatifs valides, dépendances npm connues.
const KIT_DIR = __DIR__.'/../../scaffold/resources/js/components/admin';

function kitFiles(): array
{
    return glob(KIT_DIR.'/*.{js,jsx}', GLOB_BRACE);
}

it('contient les composants du kit', function () {
    $names = array_map(fn ($file) => pathinfo($file, PATHINFO_FILENAME), kitFiles());

    expect($names)->toContain(
        'AdminLayout', 'Sidebar', 'Header', 'Breadcrumb', 'DataTable', 'Pagination', 'Filters',
        'Modal', 'ConfirmDialog', 'Input', 'Select', 'Textarea', 'Checkbox', 'Switch', 'DatePicker',
        'Button', 'Badge', 'EmptyState', 'icons', 'format', 'index',
    );
});

it('résout tous les imports relatifs du kit', function () {
    $missing = [];

    foreach (kitFiles() as $file) {
        preg_match_all('/from\s+[\'"](\.[^\'"]+)[\'"]/', file_get_contents($file), $matches);

        foreach ($matches[1] as $import) {
            $base = KIT_DIR.'/'.$import;

            if (! file_exists($base.'.js') && ! file_exists($base.'.jsx')) {
                $missing[] = basename($file).' -> '.$import;
            }
        }
    }

    expect($missing)->toBe([]);
});

it('ne dépend que de react, inertia et lucide-react', function () {
    $unknown = [];

    foreach (kitFiles() as $file) {
        preg_match_all('/from\s+[\'"]([^.\'"][^\'"]*)[\'"]/', file_get_contents($file), $matches);

        foreach ($matches[1] as $package) {
            if (! in_array($package, ['react', '@inertiajs/react', 'lucide-react'], true)) {
                $unknown[] = basename($file).' -> '.$package;
            }
        }
    }

    expect($unknown)->toBe([]);
});
