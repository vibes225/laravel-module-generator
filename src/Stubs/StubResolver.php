<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Stubs;

use RuntimeException;

/**
 * Trouve un stub : le projet (stubs publiés) prime sur le package ; une variante `nom.l<majeure>.stub`
 * prime sur `nom.stub` pour isoler les différences entre versions de Laravel.
 */
final class StubResolver
{
    public function __construct(
        private readonly string $packagePath,
        private readonly ?string $projectPath = null,
        private readonly ?int $laravelMajor = null,
    ) {}

    public function resolve(string $name): string
    {
        foreach ($this->candidates($name) as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        throw new RuntimeException("Stub introuvable : {$name}.");
    }

    public function exists(string $name): bool
    {
        foreach ($this->candidates($name) as $candidate) {
            if (is_file($candidate)) {
                return true;
            }
        }

        return false;
    }

    /** @return list<string> */
    private function candidates(string $name): array
    {
        $names = $this->laravelMajor === null ? [$name] : ["{$name}.l{$this->laravelMajor}", $name];
        $roots = array_filter([$this->projectPath, $this->packagePath]);
        $candidates = [];

        foreach ($roots as $root) {
            foreach ($names as $candidate) {
                $candidates[] = rtrim($root, '/').'/'.$candidate.'.stub';
            }
        }

        return $candidates;
    }
}
