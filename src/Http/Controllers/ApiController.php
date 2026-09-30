<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Http\Controllers;

use Amon\ModuleGenerator\Definition\DefinitionNormalizer;
use Amon\ModuleGenerator\Definition\InvalidDefinition;
use Amon\ModuleGenerator\ModuleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/** API JSON de l'interface : exactement le même moteur que la CLI (ModuleService). */
final class ApiController
{
    public function __construct(private readonly ModuleService $service) {}

    public function preview(Request $request, DefinitionNormalizer $normalizer): JsonResponse
    {
        $input = (array) $request->input('definition', []);

        try {
            $definition = $this->service->definition($input);
        } catch (InvalidDefinition $e) {
            return response()->json(['errors' => $e->errors, 'definition' => $normalizer->normalize($input)], 422);
        }

        return response()->json(['definition' => $definition->toArray(), 'plan' => $this->service->plan($definition)->toArray()]);
    }

    public function generate(Request $request): JsonResponse
    {
        abort_if(app()->environment('production'), 403, 'Outil de développement : génération refusée en production.');

        try {
            $plan = $this->service->plan($this->service->definition((array) $request->input('definition', [])));
        } catch (InvalidDefinition $e) {
            return response()->json(['errors' => $e->errors], 422);
        }

        if (! $plan->isExecutable()) {
            return response()->json(['plan' => $plan->toArray(false)], 409);
        }

        try {
            $manifest = $this->service->generate($plan);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }

        return response()->json(['manifest' => $manifest->toArray(), 'warnings' => $this->service->warnings()], 201);
    }

    public function removal(string $slug): JsonResponse
    {
        try {
            return response()->json(['plan' => $this->service->removalPlan($slug)->toArray()]);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function destroy(Request $request, string $slug): JsonResponse
    {
        abort_if(app()->environment('production'), 403, 'Outil de développement : suppression refusée en production.');

        try {
            $plan = $this->service->removalPlan($slug);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }

        return response()->json(['result' => $this->service->remove($plan, $request->boolean('include_modified'))]);
    }
}
