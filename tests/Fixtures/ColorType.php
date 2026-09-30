<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Tests\Fixtures;

use Amon\ModuleGenerator\Fields\AbstractFieldType;

/** Type ajouté par un projet hôte via `module-generator.field_types`. */
final class ColorType extends AbstractFieldType
{
    protected string $name = 'color';

    protected string $label = 'Couleur';

    protected array $allows = ['default', 'filterable'];

    protected ?string $defaultKind = 'string';
}
