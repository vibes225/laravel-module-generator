<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Fields\Types;

use Amon\ModuleGenerator\Definition\FieldDefinition;
use Amon\ModuleGenerator\Fields\AbstractFieldType;
use Amon\ModuleGenerator\Generation\ModuleNames;
use Amon\ModuleGenerator\Support\Escaper;

final class StringType extends AbstractFieldType
{
    protected string $name = 'string';

    protected string $label = 'Texte court';

    protected array $allows = ['unique', 'index', 'default', 'searchable', 'sortable', 'filterable'];

    protected array $presentation = ['searchable' => true, 'sortable' => true];

    protected array $schema = [
        'max' => ['type' => 'int', 'label' => 'Longueur maximale', 'default' => 255, 'min' => 1, 'max' => 4096],
    ];

    protected ?string $defaultKind = 'string';

    /** Valeurs de factory devinées d'après le nom du champ. */
    private const GUESSES = [
        'name' => 'fake()->name()',
        'first_name' => 'fake()->firstName()',
        'last_name' => 'fake()->lastName()',
        'title' => 'fake()->sentence(3)',
        'phone' => 'fake()->phoneNumber()',
        'city' => 'fake()->city()',
        'country' => 'fake()->country()',
        'address' => 'fake()->streetAddress()',
        'company' => 'fake()->company()',
        'code' => 'fake()->bothify(\'??-####\')',
    ];

    protected function columnArguments(FieldDefinition $field): array
    {
        $max = (int) ($field->options['max'] ?? 255);

        return $max === 255 ? [Escaper::php($field->name)] : [Escaper::php($field->name), (string) $max];
    }

    protected function typeRules(FieldDefinition $field, ModuleNames $names): array
    {
        return ["'string'", Escaper::php('max:'.($field->options['max'] ?? 255))];
    }

    protected function factoryExpression(FieldDefinition $field, ModuleNames $names): string
    {
        return self::GUESSES[$field->name] ?? 'fake()->words(3, true)';
    }

    protected function inputProps(FieldDefinition $field): array
    {
        return ['maxLength' => '{'.($field->options['max'] ?? 255).'}'];
    }
}
