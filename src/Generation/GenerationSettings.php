<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Generation;

use DateTimeImmutable;

/** Réglages résolus d'une génération (config + environnement), figés pour garder le moteur pur. */
final readonly class GenerationSettings
{
    public function __construct(
        public string $convention = 'breeze',
        public int $laravelMajor = 13,
        public string $modelsNamespace = 'App\Models',
        public DateTimeImmutable $now = new DateTimeImmutable('2026-01-01 00:00:00'),
    ) {}

    public function isStarterKit(): bool
    {
        return $this->convention === 'starter-kit';
    }
}
