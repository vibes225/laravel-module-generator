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

    protected ?string $defaultKind = 'date';

    protected string $column = 'date';

    protected ?string $castAs = 'date:Y-m-d';

    protected array $typeRules = ["'date'"];

    protected string $fragment = 'date';

    protected array $inputProps = ['type' => 'date'];

    protected string $factory = 'fake()->date()';

    protected ?string $displayHelper = 'formatDate';
}
