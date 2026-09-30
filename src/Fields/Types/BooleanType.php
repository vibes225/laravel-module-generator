<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Fields\Types;

use Amon\ModuleGenerator\Fields\AbstractFieldType;

final class BooleanType extends AbstractFieldType
{
    protected string $name = 'boolean';

    protected string $label = 'Booléen';

    protected array $allows = ['index', 'default', 'sortable', 'filterable'];

    protected array $presentation = ['filterable' => true];

    protected array $schema = [];

    protected ?string $defaultKind = 'bool';

    protected bool $pivotAllowed = true;
}
