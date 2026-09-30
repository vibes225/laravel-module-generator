<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Fields\Types;

use Amon\ModuleGenerator\Fields\AbstractFieldType;

final class StringType extends AbstractFieldType
{
    protected string $name = 'string';

    protected string $label = 'Texte court';

    protected array $allows = ['unique', 'index', 'default', 'searchable', 'sortable', 'filterable'];

    protected array $presentation = ['searchable' => true, 'sortable' => true];

    protected array $schema = ['max' => ['type' => 'int', 'label' => 'Longueur maximale', 'default' => 255, 'min' => 1, 'max' => 4096]];

    protected ?string $defaultKind = 'string';

    protected bool $pivotAllowed = true;
}
