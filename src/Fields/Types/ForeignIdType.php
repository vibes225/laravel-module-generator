<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Fields\Types;

use Amon\ModuleGenerator\Definition\FieldDefinition;
use Amon\ModuleGenerator\Fields\AbstractFieldType;
use Amon\ModuleGenerator\Generation\ModuleNames;
use Amon\ModuleGenerator\Support\Escaper;

/** Clé étrangère brute (sans relation Eloquent). Pour une relation, préférer `belongsTo`. */
final class ForeignIdType extends AbstractFieldType
{
    protected string $name = 'foreignId';

    protected string $label = 'Clé étrangère';

    protected array $allows = ['unique', 'sortable', 'filterable'];

    protected array $presentation = ['filterable' => true];

    protected array $schema = [
        'references' => ['type' => 'identifier', 'label' => 'Table référencée', 'required' => true],
        'on_delete' => ['type' => 'select', 'label' => 'À la suppression', 'default' => 'restrict', 'choices' => ['cascade', 'restrict', 'null', 'no_action']],
    ];

    protected string $column = 'foreignId';

    protected array $inputProps = ['type' => 'number', 'step' => '1'];

    protected function columnSuffix(FieldDefinition $field): string
    {
        return '->constrained('.Escaper::php($field->options['references']).')'.self::onDelete($field->options['on_delete']);
    }

    public static function onDelete(string $behaviour): string
    {
        return match ($behaviour) {
            'cascade' => '->cascadeOnDelete()',
            'null' => '->nullOnDelete()',
            'no_action' => '->noActionOnDelete()',
            default => '->restrictOnDelete()',
        };
    }

    protected function typeRules(FieldDefinition $field, ModuleNames $names): array
    {
        return ["'integer'", 'Rule::exists('.Escaper::php($field->options['references']).", 'id')"];
    }

    public function imports(FieldDefinition $field, ModuleNames $names): array
    {
        return ['Illuminate\Validation\Rule'];
    }

    protected function factoryExpression(FieldDefinition $field, ModuleNames $names): string
    {
        return $field->nullable ? 'null' : 'fake()->numberBetween(1, 10)';
    }
}
