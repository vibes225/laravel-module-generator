<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Fields\Types;

use Amon\ModuleGenerator\Fields\AbstractFieldType;

final class TimeType extends AbstractFieldType
{
    protected string $name = 'time';

    protected string $label = 'Heure';

    protected array $allows = ['index', 'default', 'sortable', 'filterable'];

    protected array $presentation = ['sortable' => true];

    protected array $schema = [];

    protected ?string $defaultKind = 'time';

    protected bool $pivotAllowed = true;
}
