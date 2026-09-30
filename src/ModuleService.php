<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator;

use Amon\ModuleGenerator\Definition\DefinitionFactory;
use Amon\ModuleGenerator\Definition\DefinitionNormalizer;
use Amon\ModuleGenerator\Definition\InvalidDefinition;
use Amon\ModuleGenerator\Definition\ModuleDefinition;
use Amon\ModuleGenerator\Generation\GenerationPlan;
use Amon\ModuleGenerator\Generation\GenerationSettings;
use Amon\ModuleGenerator\Generation\ModuleGenerator;
use Amon\ModuleGenerator\Generation\PlanExecutor;
use Amon\ModuleGenerator\Generation\PlanInspector;
use Amon\ModuleGenerator\Manifest\Manifest;
use Amon\ModuleGenerator\Manifest\ManifestRepository;
use Amon\ModuleGenerator\Registry\ModuleRegistry;
use Amon\ModuleGenerator\Removal\RemovalPlan;
use Amon\ModuleGenerator\Removal\Remover;
use Closure;

/**
 * Façade du moteur, identique pour la CLI et l'interface web :
 * entrée => définition => plan (prévisualisation) => exécution.
 */
final class ModuleService
{
    /** @param  Closure(): GenerationSettings  $settings */
    public function __construct(
        private readonly DefinitionNormalizer $normalizer,
        private readonly DefinitionFactory $factory,
        private readonly ModuleGenerator $generator,
        private readonly ModuleRegistry $registry,
        private readonly ManifestRepository $manifests,
        private readonly PlanInspector $inspector,
        private readonly PlanExecutor $executor,
        private readonly Closure $settings,
        private readonly Remover $remover,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     *
     * @throws InvalidDefinition
     */
    public function definition(array $input): ModuleDefinition
    {
        return $this->factory->make($this->resolve($input));
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, list<string>>
     */
    public function errors(array $input): array
    {
        return $this->factory->errors($this->resolve($input));
    }

    public function plan(ModuleDefinition $definition): GenerationPlan
    {
        return $this->inspector->inspect($this->generator->plan($definition, ($this->settings)()));
    }

    public function generate(GenerationPlan $plan): Manifest
    {
        return $this->executor->execute($plan, ($this->settings)()->now->format(DATE_ATOM));
    }

    /** @return list<string> */
    public function warnings(): array
    {
        return $this->executor->warnings();
    }

    /**
     * Définition archivée (après suppression) pour régénérer avec `--from`.
     *
     * @return array<string, mixed>|null
     */
    public function archivedInput(string $reference): ?array
    {
        return $this->manifests->findArchived($reference)?->definition;
    }

    public function removalPlan(string $slug): RemovalPlan
    {
        return $this->remover->plan($slug);
    }

    /**
     * @return array{deleted: list<string>, kept: list<string>, missing: list<string>, directories: list<string>, archive: ?string}
     */
    public function remove(RemovalPlan $plan, bool $includeModified): array
    {
        return $this->remover->execute($plan, $includeModified, ($this->settings)()->now->format('Ymd-His'));
    }

    public function registry(): ModuleRegistry
    {
        return $this->registry;
    }

    /**
     * Complète les relations qui utilisent un module pivot existant : modèle, table et clés lus dans son manifest.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function resolve(array $input): array
    {
        $input = $this->normalizer->normalize($input);

        foreach ($input['relations'] as $i => $relation) {
            $slug = $relation['pivot']['module'] ?? null;

            if (! is_array($relation) || ($relation['pivot']['mode'] ?? null) !== 'module' || ! is_string($slug)) {
                continue;
            }

            $module = $this->registry->find($slug);

            if ($module === null || ! $module->isComplete() || ($module->definition['kind'] ?? null) !== 'pivot') {
                continue;
            }

            $pivot = $module->definition['pivot'];
            $input['relations'][$i]['pivot']['model'] = $module->model();
            $input['relations'][$i]['pivot']['table'] = $module->table();

            foreach (['left' => 'right', 'right' => 'left'] as $own => $other) {
                if (($pivot[$own]['model'] ?? null) === $input['model'] && ($pivot[$other]['model'] ?? null) === $relation['target']) {
                    $input['relations'][$i]['foreign_pivot_key'] = $pivot[$own]['foreign_key'];
                    $input['relations'][$i]['related_pivot_key'] = $pivot[$other]['foreign_key'];
                    break;
                }
            }
        }

        return $input;
    }
}
