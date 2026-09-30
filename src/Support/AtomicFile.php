<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Support;

use RuntimeException;

/** Écriture atomique : fichier temporaire dans le même dossier puis renommage. */
final class AtomicFile
{
    public static function write(string $path, string $content): void
    {
        $directory = dirname($path);

        if (! is_dir($directory)) {
            for ($ancestor = $directory; $ancestor !== dirname($ancestor); $ancestor = dirname($ancestor)) {
                if (file_exists($ancestor) && ! is_dir($ancestor)) {
                    throw new RuntimeException("Impossible de créer le dossier {$directory} : {$ancestor} est un fichier.");
                }
            }

            if (! mkdir($directory, 0777, true) && ! is_dir($directory)) {
                throw new RuntimeException("Impossible de créer le dossier {$directory}.");
            }
        }

        $temporary = $directory.DIRECTORY_SEPARATOR.'.'.basename($path).'.'.bin2hex(random_bytes(4)).'.tmp';

        if (file_put_contents($temporary, $content) === false) {
            throw new RuntimeException("Impossible d'écrire {$temporary}.");
        }

        if (! @rename($temporary, $path)) {
            @unlink($temporary);

            throw new RuntimeException("Impossible de renommer le fichier temporaire vers {$path}.");
        }
    }
}
