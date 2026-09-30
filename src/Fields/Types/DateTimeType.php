<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Fields\Types;

use Amon\ModuleGenerator\Fields\AbstractFieldType;

final class DateTimeType extends AbstractFieldType
{
    protected string $name = 'datetime';

    protected string $label = 'Date et heure';

    protected array $allows = ['index', 'default', 'sortable', 'filterable'];

    protected array $presentation = ['sortable' => true];

    protected array $schema = [];

    protected ?string $defaultKind = 'datetime';

    protected bool $pivotAllowed = true;
}
