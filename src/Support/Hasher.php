<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Support;

/** Empreinte stable d'un contenu texte : sans BOM, fins de ligne LF. */
final class Hasher
{
    public static function normalize(string $content): string
    {
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }

        return str_replace(["\r\n", "\r"], "\n", $content);
    }

    public static function hash(string $content): string
    {
        return hash('sha256', self::normalize($content));
    }
}
