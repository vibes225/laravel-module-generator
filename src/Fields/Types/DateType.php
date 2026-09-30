<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Fields\Types;

use Amon\ModuleGenerator\Fields\AbstractFieldType;

final class DateType extends AbstractFieldType
{
    protected string $name = 'date';

    protected string $label = 'Date';

    protected array $allows = ['index', 'default', 'sortable', 'filterable'];

    protected array $presentation = ['sortable' => true];

    protected array $schema = [];

    protected ?string $defaultKind = 'date';

    protected bool $pivotAllowed = true;
}
