<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Generation\Formatting;

use Symfony\Component\Process\Process;

/** Pint (PHP) et Prettier (JSX) de l'hôte, s'ils sont activés et installés. */
final class ProcessFormatter implements Formatter
{
    public function __construct(
        private readonly bool $pint = true,
        private readonly bool $prettier = false,
    ) {}

    public function format(string $basePath, array $paths): array
    {
        $warnings = [];
        $php = array_values(array_filter($paths, fn (string $path) => str_ends_with($path, '.php')));
        $jsx = array_values(array_filter($paths, fn (string $path) => str_ends_with($path, '.jsx')));

        if ($this->pint && $php !== []) {
            $binary = $basePath.'/vendor/bin/pint';

            if (is_file($binary)) {
                $warnings = [...$warnings, ...$this->run([PHP_BINARY, $binary, ...$php], $basePath, 'Pint')];
            }
        }

        if ($this->prettier && $jsx !== []) {
            $warnings = [...$warnings, ...$this->run(['npx', '--no-install', 'prettier', '--write', ...$jsx], $basePath, 'Prettier')];
        }

        return $warnings;
    }

    /**
     * @param  list<string>  $command
     * @return list<string>
     */
    private function run(array $command, string $basePath, string $tool): array
    {
        $process = new Process($command, $basePath, timeout: 120);
        $process->run();

        return $process->isSuccessful() ? [] : ["{$tool} a échoué : ".trim($process->getErrorOutput() ?: $process->getOutput())];
    }
}
