<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Stubs;

use RuntimeException;

/**
 * Remplacement de placeholders `%%NOM%%` (majuscules) : aucune logique dans les stubs, pas de Blade
 * (les accolades JSX restent intactes). Un placeholder sans valeur est une erreur.
 */
final class StubRenderer
{
    private const PLACEHOLDER = '/%%([A-Z][A-Z0-9_]*)%%/';

    /** @var array<string, string> */
    private array $cache = [];

    public function __construct(private readonly StubResolver $resolver) {}

    /** @param  array<string, string>  $values */
    public function render(string $stub, array $values): string
    {
        return $this->replace($this->template($stub), $values, $stub);
    }

    /** @param  array<string, string>  $values */
    public function replace(string $template, array $values, string $source = 'template'): string
    {
        return (string) preg_replace_callback(self::PLACEHOLDER, function (array $match) use ($values, $source) {
            if (! array_key_exists($match[1], $values)) {
                throw new RuntimeException("Valeur manquante pour %%{$match[1]}%% dans {$source}.");
            }

            return $values[$match[1]];
        }, $template);
    }

    public function has(string $stub): bool
    {
        return $this->resolver->exists($stub);
    }

    private function template(string $stub): string
    {
        return $this->cache[$stub] ??= str_replace("\r\n", "\n", (string) file_get_contents($this->resolver->resolve($stub)));
    }
}
