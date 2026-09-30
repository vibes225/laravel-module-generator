<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Generation\Formatting;

interface Formatter
{
    /**
     * Formate les fichiers (chemins relatifs à `$basePath`). Un échec n'annule pas la génération.
     *
     * @param  list<string>  $paths
     * @return list<string> avertissements
     */
    public function format(string $basePath, array $paths): array;
}
