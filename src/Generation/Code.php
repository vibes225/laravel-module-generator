<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Generation;

use Amon\ModuleGenerator\Support\Escaper;

/** Petits utilitaires de mise en forme du code généré (sortie déjà conforme à Pint). */
final class Code
{
    /** @param  list<string>  $classes */
    public static function uses(array $classes, string $namespace = ''): string
    {
        $classes = array_values(array_unique(array_filter(
            $classes,
            fn (string $class) => self::namespaceOf($class) !== $namespace,
        )));
        usort($classes, fn (string $a, string $b) => strcasecmp(str_replace(chr(92), ' ', $a), str_replace(chr(92), ' ', $b)));

        return implode("\n", array_map(fn (string $class) => "use {$class};", $classes));
    }

    public static function basename(string $class): string
    {
        $position = strrpos($class, chr(92));

        return $position === false ? $class : substr($class, $position + 1);
    }

    public static function namespaceOf(string $class): string
    {
        $position = strrpos($class, chr(92));

        return $position === false ? '' : substr($class, 0, $position);
    }

    /** Indente chaque ligne non vide de `$levels` niveaux (4 espaces). */
    public static function indent(string $code, int $levels): string
    {
        $pad = str_repeat('    ', $levels);

        return implode("\n", array_map(fn (string $line) => $line === '' ? '' : $pad.$line, explode("\n", $code)));
    }

    /** @param  list<string>  $values */
    public static function stringList(array $values): string
    {
        return implode(', ', array_map(Escaper::php(...), $values));
    }

    /**
     * Lignes `'clé' => valeur,` indentées.
     *
     * @param  array<string, string>  $pairs  valeurs en expressions PHP
     */
    public static function arrayLines(array $pairs, int $levels): string
    {
        $lines = [];

        foreach ($pairs as $key => $value) {
            $lines[] = Escaper::php((string) $key).' => '.$value.',';
        }

        return self::indent(implode("\n", $lines), $levels);
    }

    /**
     * Tableau PHP multiligne d'une liste de chaînes, ou `[]`.
     *
     * @param  list<string>  $values
     */
    public static function listBlock(array $values, int $levels): string
    {
        if ($values === []) {
            return '[]';
        }

        $inner = self::indent(implode("\n", array_map(fn (string $value) => Escaper::php($value).',', $values)), $levels + 1);

        return "[\n{$inner}\n".str_repeat('    ', $levels).']';
    }
}
