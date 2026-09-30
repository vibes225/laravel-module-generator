<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Definition;

use InvalidArgumentException;

final class InvalidDefinition extends InvalidArgumentException
{
    /** @param  array<string, list<string>>  $errors  messages par chemin (ex. `fields.2.name`) */
    public function __construct(public readonly array $errors)
    {
        $lines = [];

        foreach ($errors as $path => $messages) {
            foreach ($messages as $message) {
                $lines[] = "{$path} : {$message}";
            }
        }

        parent::__construct("Définition de module invalide :\n".implode("\n", $lines));
    }
}
