<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Generation\Generators;

use Amon\ModuleGenerator\Contracts\Generator;
use Amon\ModuleGenerator\Generation\Facts;
use Amon\ModuleGenerator\Generation\GenerationContext;
use Amon\ModuleGenerator\Generation\PlannedFile;

/** `routes/modules/<slug>.php`, chargé par `routes/admin-modules.php` (préfixe admin, noms admin., auth). */
final class RouteGenerator implements Generator
{
    public function applies(GenerationContext $context): bool
    {
        return true;
    }

    public function generate(GenerationContext $context): array
    {
        $names = $context->names;
        $pivot = $context->definition->pivot;

        $content = Facts::isPair($context->definition)
            ? $context->render('routes-pair', [
                'CONTROLLER' => $names->controller,
                'SLUG' => $names->slug,
                'LEFT_PARAMETER' => (string) $pivot->left->foreignKey,
                'RIGHT_PARAMETER' => (string) $pivot->right->foreignKey,
            ])
            : $context->render('routes', [
                'CONTROLLER' => $names->controller,
                'SLUG' => $names->slug,
                'PARAMETER' => $names->routeParameter,
            ]);

        return [new PlannedFile("routes/modules/{$names->slug}.php", $content, 'routes', 'Routes du module')];
    }
}
