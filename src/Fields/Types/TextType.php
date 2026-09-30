<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Fields\Types;

use Amon\ModuleGenerator\Fields\AbstractFieldType;

final class TextType extends AbstractFieldType
{
    protected string $name = 'text';

    protected string $label = 'Texte long';

    protected array $allows = ['searchable'];

    protected array $presentation = ['in_table' => false];

    protected string $column = 'text';

    protected array $typeRules = ["'string'"];

    protected string $fragment = 'textarea';

    protected string $factory = 'fake()->paragraph()';
}
