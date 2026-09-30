<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Fields\Types;

use Amon\ModuleGenerator\Definition\FieldDefinition;
use Amon\ModuleGenerator\Fields\AbstractFieldType;
use Amon\ModuleGenerator\Generation\ModuleNames;
use Amon\ModuleGenerator\Support\Escaper;

final class EmailType extends AbstractFieldType
{
    protected string $name = 'email';

    protected string $label = 'Email';

    protected array $allows = ['unique', 'index', 'searchable', 'sortable', 'filterable'];

    protected array $presentation = ['searchable' => true, 'sortable' => true];

    protected array $schema = [
        'max' => ['type' => 'int', 'label' => 'Longueur maximale', 'default' => 255, 'min' => 1, 'max' => 4096],
    ];

    protected array $inputProps = ['type' => 'email'];

    protected string $factory = 'fake()->safeEmail()';

    protected function columnArguments(FieldDefinition $field): array
    {
        $max = (int) ($field->options['max'] ?? 255);

        return $max === 255 ? [Escaper::php($field->name)] : [Escaper::php($field->name), (string) $max];
    }

    protected function typeRules(FieldDefinition $field, ModuleNames $names): array
    {
        return ["'email'", Escaper::php('max:'.($field->options['max'] ?? 255))];
    }
}
