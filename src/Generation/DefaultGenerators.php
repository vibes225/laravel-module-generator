<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Generation;

use Amon\ModuleGenerator\Contracts\Generator;
use Amon\ModuleGenerator\Generation\Generators\ControllerGenerator;
use Amon\ModuleGenerator\Generation\Generators\EnumGenerator;
use Amon\ModuleGenerator\Generation\Generators\FactoryGenerator;
use Amon\ModuleGenerator\Generation\Generators\FilterGenerator;
use Amon\ModuleGenerator\Generation\Generators\MenuGenerator;
use Amon\ModuleGenerator\Generation\Generators\MigrationGenerator;
use Amon\ModuleGenerator\Generation\Generators\ModelGenerator;
use Amon\ModuleGenerator\Generation\Generators\RequestGenerator;
use Amon\ModuleGenerator\Generation\Generators\RouteGenerator;

final class DefaultGenerators
{
    /** @return list<Generator> */
    public static function all(): array
    {
        return [
            new MigrationGenerator,
            new EnumGenerator,
            new ModelGenerator,
            new RequestGenerator,
            new FilterGenerator,
            new ControllerGenerator,
            new FactoryGenerator,
            new RouteGenerator,
            new MenuGenerator,
        ];
    }
}
