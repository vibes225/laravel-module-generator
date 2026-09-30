<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Fields\Types;

use Amon\ModuleGenerator\Definition\FieldDefinition;
use Amon\ModuleGenerator\Fields\AbstractFieldType;
use Amon\ModuleGenerator\Generation\ModuleNames;

final class BooleanType extends AbstractFieldType
{
    protected string $name = 'boolean';

    protected string $label = 'Booléen';

    protected array $allows = ['index', 'default', 'sortable', 'filterable'];

    protected array $presentation = ['filterable' => true];

    protected ?string $defaultKind = 'bool';

    protected string $column = 'boolean';

    protected ?string $castAs = 'boolean';

    protected array $typeRules = ["'boolean'"];

    protected string $fragment = 'switch';

    protected string $factory = 'fake()->boolean()';

    public function migrationColumn(FieldDefinition $field): string
    {
        $column = parent::migrationColumn($field);

        // Un booléen obligatoire sans défaut explicite vaut false.
        return $field->default === null && ! $field->nullable ? $column.'->default(false)' : $column;
    }

    public function formInitial(FieldDefinition $field): string
    {
        return "record?.{$field->name} ?? ".($field->default === true ? 'true' : 'false');
    }

    public function display(FieldDefinition $field, string $variable): string
    {
        $value = "{$variable}.{$field->name}";

        return "<Badge tone={{$value} ? 'green' : 'slate'}>{formatBoolean({$value})}</Badge>";
    }

    public function displayImports(FieldDefinition $field): array
    {
        return ['Badge', 'formatBoolean'];
    }

    public function optionsExpression(FieldDefinition $field, ModuleNames $names): string
    {
        return "[['value' => '1', 'label' => 'Oui'], ['value' => '0', 'label' => 'Non']]";
    }
}
