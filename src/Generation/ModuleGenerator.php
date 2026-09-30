<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Generation;

use Amon\ModuleGenerator\Contracts\Generator;
use Amon\ModuleGenerator\Definition\ModuleDefinition;
use Amon\ModuleGenerator\Fields\FieldTypeRegistry;
use Amon\ModuleGenerator\Stubs\StubRenderer;

/** Orchestrateur pur : définition + générateurs => plan. Même moteur pour la CLI et l'interface web. */
final class ModuleGenerator
{
    /** @param  list<Generator>  $generators */
    public function __construct(
        private readonly array $generators,
        private readonly FieldTypeRegistry $types,
        private readonly StubRenderer $renderer,
    ) {}

    public function plan(ModuleDefinition $definition, GenerationSettings $settings): GenerationPlan
    {
        $context = new GenerationContext($definition, $this->types, $settings, $this->renderer);
        $files = [];

        foreach ($this->generators as $generator) {
            if ($generator->applies($context)) {
                array_push($files, ...$generator->generate($context));
            }
        }

        return new GenerationPlan($definition, $files, $this->notes($definition));
    }

    /** @return list<string> */
    private function notes(ModuleDefinition $definition): array
    {
        $notes = [];

        if ($definition->morph !== null) {
            $notes[] = "Relation polymorphe « {$definition->morph->name} » : aucune clé étrangère en base (limite des relations polymorphes).";
        }

        if ($definition->tree) {
            $notes[] = 'Structure en arbre : une base de données transactionnelle est recommandée (kalnoy/nestedset).';
        }

        foreach ($definition->relations as $relation) {
            if ($relation->foreignKey !== null && $relation->type->value === 'belongsTo') {
                $notes[] = "La table {$relation->targetTable} doit exister avant d'exécuter la migration (clé étrangère {$relation->foreignKey}).";
            }
        }

        return $notes;
    }
}
