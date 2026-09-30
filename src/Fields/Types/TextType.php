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

    protected array $schema = [];

    protected ?string $defaultKind = null;

    protected bool $pivotAllowed = true;
}
