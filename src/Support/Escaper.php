<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Support;

use InvalidArgumentException;

/** Échappement contextuel des valeurs insérées dans le code généré. */
final class Escaper
{
    /** Littéral chaîne PHP entre apostrophes. */
    public static function php(string $value): string
    {
        return "'".strtr($value, [chr(92) => chr(92).chr(92), "'" => chr(92)."'"])."'";
    }

    /** Littéral chaîne JavaScript entre apostrophes. */
    public static function js(string $value): string
    {
        $b = chr(92);

        return "'".strtr($value, [$b => $b.$b, "'" => $b."'", "\n" => $b.'n', "\r" => $b.'r', '</' => '<'.$b.'/'])."'";
    }

    /** Texte inséré dans du JSX (entre balises). */
    public static function jsx(string $value): string
    {
        return strtr($value, ['&' => '&amp;', '<' => '&lt;', '>' => '&gt;', '{' => '&#123;', '}' => '&#125;']);
    }

    /** Valide un identifiant avant insertion brute dans du code. */
    public static function identifier(string $value, string $pattern = '/^[A-Za-z_][A-Za-z0-9_]*$/'): string
    {
        if (! preg_match($pattern, $value)) {
            throw new InvalidArgumentException("Identifiant refusé : {$value}");
        }

        return $value;
    }

    /** Valeur scalaire PHP (défauts de colonnes, factories). */
    public static function phpValue(mixed $value): string
    {
        return match (true) {
            $value === null => 'null',
            is_bool($value) => $value ? 'true' : 'false',
            is_int($value), is_float($value) => var_export($value, true),
            is_string($value) => self::php($value),
            default => throw new InvalidArgumentException('Valeur non scalaire.'),
        };
    }
}
