<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Definition;

use JsonException;

/** Point d'entrée unique (UI, CLI, manifest) : normalise, valide, puis construit la définition immuable. */
final class DefinitionFactory
{
    public function __construct(
        private readonly DefinitionNormalizer $normalizer,
        private readonly DefinitionValidator $validator,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     *
     * @throws InvalidDefinition
     */
    public function make(array $input): ModuleDefinition
    {
        $normalized = $this->normalizer->normalize($input);
        $errors = $this->validator->validate($normalized);

        if ($errors !== []) {
            throw new InvalidDefinition($errors);
        }

        return ModuleDefinition::fromArray($normalized);
    }

    /**
     * Erreurs d'une entrée sans lever d'exception (formulaires de l'UI).
     *
     * @param  array<string, mixed>  $input
     * @return array<string, list<string>>
     */
    public function errors(array $input): array
    {
        return $this->validator->validate($this->normalizer->normalize($input));
    }

    /** @throws InvalidDefinition */
    public function fromJson(string $json): ModuleDefinition
    {
        try {
            $input = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidDefinition(['json' => ['JSON invalide : '.$e->getMessage()]]);
        }

        if (! is_array($input)) {
            throw new InvalidDefinition(['json' => ['Un objet JSON est attendu.']]);
        }

        return $this->make($input);
    }
}
