<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Fields;

use Amon\ModuleGenerator\Definition\FieldDefinition;
use Amon\ModuleGenerator\Generation\ModuleNames;
use Amon\ModuleGenerator\Support\Escaper;

/** Comportement commun des entiers (integer, bigInteger). */
abstract class IntegerLike extends AbstractFieldType
{
    protected array $allows = ['unique', 'index', 'default', 'sortable', 'filterable'];

    protected array $presentation = ['sortable' => true];

    protected array $schema = [
        'unsigned' => ['type' => 'bool', 'label' => 'Non signé', 'default' => false],
        'min' => ['type' => 'int', 'label' => 'Valeur minimale'],
        'max' => ['type' => 'int', 'label' => 'Valeur maximale'],
    ];

    protected ?string $defaultKind = 'int';

    protected ?string $castAs = 'integer';

    protected array $inputProps = ['type' => 'number', 'step' => '1'];

    protected ?string $displayHelper = 'formatNumber';

    protected function columnMethod(FieldDefinition $field): string
    {
        return ($field->options['unsigned'] ?? false) ? 'unsigned'.ucfirst($this->name) : $this->name;
    }

    protected function typeRules(FieldDefinition $field, ModuleNames $names): array
    {
        $rules = ["'integer'"];
        $min = $field->options['min'] ?? (($field->options['unsigned'] ?? false) ? 0 : null);

        if ($min !== null) {
            $rules[] = Escaper::php('min:'.$min);
        }

        if (isset($field->options['max'])) {
            $rules[] = Escaper::php('max:'.$field->options['max']);
        }

        return $rules;
    }

    protected function factoryExpression(FieldDefinition $field, ModuleNames $names): string
    {
        $min = $field->options['min'] ?? 0;
        $max = $field->options['max'] ?? max($min + 1000, 1000);

        return "fake()->numberBetween({$min}, {$max})";
    }
}
