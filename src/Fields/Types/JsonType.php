<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Fields\Types;

use Amon\ModuleGenerator\Definition\FieldDefinition;
use Amon\ModuleGenerator\Fields\AbstractFieldType;

final class JsonType extends AbstractFieldType
{
    protected string $name = 'json';

    protected string $label = 'JSON';

    protected array $presentation = ['in_table' => false];

    protected string $column = 'json';

    protected ?string $castAs = 'array';

    protected array $typeRules = ["'json'"];

    protected string $fragment = 'json';

    protected string $factory = '[]';

    public function formInitial(FieldDefinition $field): string
    {
        return "record?.{$field->name} ? JSON.stringify(record.{$field->name}, null, 2) : ''";
    }

    public function display(FieldDefinition $field, string $variable): string
    {
        $value = "{$variable}.{$field->name}";

        return "{$value} == null ? '—' : <code className=\"text-xs\">{JSON.stringify({$value})}</code>";
    }
}
