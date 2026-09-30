<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Generation\Generators;

use Amon\ModuleGenerator\Contracts\Generator;
use Amon\ModuleGenerator\Generation\GenerationContext;
use Amon\ModuleGenerator\Generation\PlannedFile;
use Amon\ModuleGenerator\Support\Escaper;

/** Entrée de menu `config/admin/menu/<slug>.php` : nom de route (compatible config:cache). */
final class MenuGenerator implements Generator
{
    public function applies(GenerationContext $context): bool
    {
        return $context->definition->menu->enabled;
    }

    public function generate(GenerationContext $context): array
    {
        $definition = $context->definition;
        $menu = $definition->menu;

        return [new PlannedFile(
            "config/admin/menu/{$definition->slug}.php",
            $context->render('menu', [
                'LABEL' => Escaper::php($definition->name),
                'ROUTE' => $context->names->routeName,
                'ICON' => $definition->icon,
                'GROUP' => $menu->group,
                'ORDER' => (string) $menu->order,
                'PERMISSION' => $menu->permission === null ? 'null' : Escaper::php($menu->permission),
            ]),
            'menu',
            'Entrée de menu',
        )];
    }
}
