<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Fields\Types;

use Amon\ModuleGenerator\Fields\AbstractFieldType;

final class UuidType extends AbstractFieldType
{
    protected string $name = 'uuid';

    protected string $label = 'UUID';

    protected array $allows = ['unique', 'index', 'searchable', 'sortable', 'filterable'];

    protected array $presentation = [];

    protected array $schema = [];

    protected ?string $defaultKind = null;

    protected bool $pivotAllowed = true;
}
