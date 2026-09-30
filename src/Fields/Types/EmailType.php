<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Fields\Types;

use Amon\ModuleGenerator\Fields\AbstractFieldType;

final class EmailType extends AbstractFieldType
{
    protected string $name = 'email';

    protected string $label = 'Email';

    protected array $allows = ['unique', 'index', 'searchable', 'sortable', 'filterable'];

    protected array $presentation = ['searchable' => true, 'sortable' => true];

    protected array $schema = ['max' => ['type' => 'int', 'label' => 'Longueur maximale', 'default' => 255, 'min' => 1, 'max' => 4096]];

    protected ?string $defaultKind = null;

    protected bool $pivotAllowed = true;
}
