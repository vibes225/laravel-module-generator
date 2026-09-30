<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Fields\Types;

use Amon\ModuleGenerator\Definition\FieldDefinition;
use Amon\ModuleGenerator\Fields\AbstractFieldType;
use Amon\ModuleGenerator\Generation\ModuleNames;
use Amon\ModuleGenerator\Support\Escaper;

final class DecimalType extends AbstractFieldType
{
    protected string $name = 'decimal';

    protected string $label = 'Décimal';

    protected array $allows = ['unique', 'index', 'default', 'sortable', 'filterable'];

    protected array $presentation = ['sortable' => true];

    protected array $schema = [
        'precision' => ['type' => 'int', 'label' => 'Précision (chiffres au total)', 'default' => 10, 'min' => 1, 'max' => 65],
        'scale' => ['type' => 'int', 'label' => 'Décimales', 'default' => 2, 'min' => 0, 'max' => 30],
        'min' => ['type' => 'int', 'label' => 'Valeur minimale'],
    ];

    protected ?string $defaultKind = 'number';

    protected string $column = 'decimal';

    public function validateOptions(array $options): array
    {
        $errors = parent::validateOptions($options);
        $precision = $options['precision'] ?? 10;
        $scale = $options['scale'] ?? 2;

        if (! isset($errors['scale']) && ! isset($errors['precision']) && $scale > $precision) {
            $errors['scale'] = 'Les décimales ne peuvent pas dépasser la précision.';
        }

        return $errors;
    }

    protected function columnArguments(FieldDefinition $field): array
    {
        return [Escaper::php($field->name), (string) $field->options['precision'], (string) $field->options['scale']];
    }

    public function cast(FieldDefinition $field, ModuleNames $names): string
    {
        return Escaper::php('decimal:'.$field->options['scale']);
    }

    protected function typeRules(FieldDefinition $field, ModuleNames $names): array
    {
        $rules = ["'numeric'", Escaper::php('decimal:0,'.$field->options['scale'])];

        if (isset($field->options['min'])) {
            $rules[] = Escaper::php('min:'.$field->options['min']);
        }

        return $rules;
    }

    protected function factoryExpression(FieldDefinition $field, ModuleNames $names): string
    {
        $min = $field->options['min'] ?? 0;

        return "fake()->randomFloat({$field->options['scale']}, {$min}, ".($min + 10000).')';
    }

    protected function inputProps(FieldDefinition $field): array
    {
        $scale = (int) $field->options['scale'];

        return ['type' => 'number', 'step' => $scale === 0 ? '1' : '0.'.str_repeat('0', $scale - 1).'1'];
    }

    public function display(FieldDefinition $field, string $variable): string
    {
        $scale = (int) $field->options['scale'];

        return "formatNumber({$variable}.{$field->name}, { minimumFractionDigits: {$scale}, maximumFractionDigits: {$scale} })";
    }

    public function displayImports(FieldDefinition $field): array
    {
        return ['formatNumber'];
    }
}
