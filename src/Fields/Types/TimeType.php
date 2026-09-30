<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Fields\Types;

use Amon\ModuleGenerator\Definition\FieldDefinition;
use Amon\ModuleGenerator\Fields\AbstractFieldType;

final class TimeType extends AbstractFieldType
{
    protected string $name = 'time';

    protected string $label = 'Heure';

    protected array $allows = ['index', 'default', 'sortable', 'filterable'];

    protected array $presentation = ['sortable' => true];

    protected ?string $defaultKind = 'time';

    protected string $column = 'time';

    protected array $typeRules = ["'date_format:H:i,H:i:s'"];

    protected array $inputProps = ['type' => 'time'];

    protected string $factory = "fake()->time('H:i')";

    public function display(FieldDefinition $field, string $variable): string
    {
        $value = "{$variable}.{$field->name}";

        return "{$value} ? {$value}.slice(0, 5) : '—'";
    }
}
