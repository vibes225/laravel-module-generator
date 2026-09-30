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

    protected ?string $defaultKind = 'datetime';

    protected string $column = 'dateTime';

    protected ?string $castAs = 'datetime:Y-m-d H:i:s';

    protected array $typeRules = ["'date'"];

    protected string $fragment = 'date';

    protected array $inputProps = ['type' => 'datetime-local'];

    protected string $factory = "fake()->dateTime()->format('Y-m-d H:i:s')";

    protected ?string $displayHelper = 'formatDateTime';
}
