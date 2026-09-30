<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Naming;

use Illuminate\Support\Str;

/**
 * Pluriel des noms de table : règles françaises (activables) ou pluriel anglais de Laravel.
 * Travaille sur des mots ASCII en minuscules (snake_case) ; seul le dernier mot est accordé.
 */
final class Inflector
{
    private const IRREGULAR = [
        'oeil' => 'yeux',
        'ciel' => 'cieux',
        'monsieur' => 'messieurs',
        'madame' => 'mesdames',
        'mademoiselle' => 'mesdemoiselles',
    ];

    private const AL_TAKES_S = ['bal', 'cal', 'carnaval', 'chacal', 'festival', 'narval', 'pal', 'recital', 'regal', 'serval', 'val'];

    private const AIL_TAKES_AUX = ['bail', 'corail', 'email', 'soupirail', 'travail', 'vantail', 'ventail', 'vitrail'];

    private const AU_EU_TAKES_S = ['bleu', 'emeu', 'landau', 'lieu', 'pneu', 'sarrau', 'unau'];

    private const OU_TAKES_X = ['bijou', 'caillou', 'chou', 'genou', 'hibou', 'joujou', 'pou'];

    public function __construct(private readonly bool $french = false) {}

    /** Nom de table dérivé d'un nom de modèle : `ClientContact` => `client_contacts`. */
    public function table(string $model): string
    {
        return $this->pluralizeSnake(Str::snake($model));
    }

    /** Pluralise le dernier mot d'une chaîne snake_case. */
    public function pluralizeSnake(string $snake): string
    {
        $words = explode('_', $snake);
        $last = array_pop($words);
        $words[] = $this->plural($last);

        return implode('_', $words);
    }

    public function plural(string $word): string
    {
        return $this->french ? $this->frenchPlural($word) : Str::plural($word);
    }

    private function frenchPlural(string $word): string
    {
        if ($word === '') {
            return $word;
        }

        if (isset(self::IRREGULAR[$word])) {
            return self::IRREGULAR[$word];
        }

        if (preg_match('/[sxz]$/', $word)) {
            return $word;
        }

        return match (true) {
            str_ends_with($word, 'al') => in_array($word, self::AL_TAKES_S, true) ? $word.'s' : substr($word, 0, -2).'aux',
            str_ends_with($word, 'ail') => in_array($word, self::AIL_TAKES_AUX, true) ? substr($word, 0, -3).'aux' : $word.'s',
            (bool) preg_match('/(eau|au|eu)$/', $word) => in_array($word, self::AU_EU_TAKES_S, true) ? $word.'s' : $word.'x',
            str_ends_with($word, 'ou') => in_array($word, self::OU_TAKES_X, true) ? $word.'x' : $word.'s',
            default => $word.'s',
        };
    }
}
