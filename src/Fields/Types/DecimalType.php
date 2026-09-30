<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Fields\Types;

use Amon\ModuleGenerator\Fields\AbstractFieldType;

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
}
