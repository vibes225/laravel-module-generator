<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Fields\Types;

use Amon\ModuleGenerator\Definition\FieldDefinition;
use Amon\ModuleGenerator\Fields\AbstractFieldType;
use Amon\ModuleGenerator\Generation\ModuleNames;
use Illuminate\Support\Str;

/**
 * Options : `values` = liste de { value, label }. Accepte aussi une liste de chaînes (forme CLI).
 * Génère une classe d'enum PHP appartenant au module.
 */
final class EnumType extends AbstractFieldType
{
    protected string $name = 'enum';

    protected string $label = 'Liste de valeurs (enum)';

    protected array $allows = ['index', 'default', 'sortable', 'filterable'];

    protected array $presentation = ['sortable' => true, 'filterable' => true];

    protected array $schema = [
        'values' => ['type' => 'enum_values', 'label' => 'Valeurs', 'required' => true],
    ];

    protected ?string $defaultKind = 'enum';

    public function normalizeOptions(array $options): array
    {
        if (isset($options['values']) && is_array($options['values'])) {
            $options['values'] = array_map(function ($item) {
                if (is_string($item)) {
                    return ['value' => $item, 'label' => ucfirst(str_replace('_', ' ', $item))];
                }

                if (is_array($item) && isset($item['value']) && is_string($item['value']) && ! isset($item['label'])) {
                    $item['label'] = ucfirst(str_replace('_', ' ', $item['value']));
                }

                return $item;
            }, array_values($options['values']));
        }

        return $options;
    }

    protected function validateOption(mixed $value, array $definition): ?string
    {
        if (! is_array($value) || ! array_is_list($value) || $value === []) {
            return 'Au moins une valeur est requise.';
        }

        $seen = [];

        foreach ($value as $item) {
            if (! is_array($item) || ! is_string($item['value'] ?? null) || ! is_string($item['label'] ?? null) || $item['label'] === '') {
                return 'Chaque valeur doit avoir un identifiant et un libellé.';
            }

            if (! preg_match('/^[a-z][a-z0-9_]*$/', $item['value'])) {
                return "Valeur « {$item['value']} » invalide (minuscules, chiffres et _, commence par une lettre).";
            }

            $case = Str::studly($item['value']);

            if (isset($seen[$case])) {
                return "Valeur en double : {$item['value']}.";
            }

            $seen[$case] = true;
        }

        return null;
    }

    protected function validateEnumDefault(mixed $value, array $options): ?string
    {
        $values = array_column($options['values'] ?? [], 'value');

        return is_string($value) && in_array($value, $values, true) ? null : 'La valeur par défaut doit faire partie des valeurs.';
    }

    // --- Génération : la colonne est une chaîne, le modèle caste vers une enum PHP propre au module.

    protected string $fragment = 'select';

    public function enumClass(FieldDefinition $field, ModuleNames $names): string
    {
        return sprintf('App\Enums\%s', $names->enumClass($field->name));
    }

    public function cast(FieldDefinition $field, ModuleNames $names): string
    {
        return $names->enumClass($field->name).'::class';
    }

    protected function typeRules(FieldDefinition $field, ModuleNames $names): array
    {
        return ['Rule::enum('.$names->enumClass($field->name).'::class)'];
    }

    public function imports(FieldDefinition $field, ModuleNames $names): array
    {
        return [$this->enumClass($field, $names), 'Illuminate\Validation\Rule'];
    }

    protected function factoryExpression(FieldDefinition $field, ModuleNames $names): string
    {
        return 'fake()->randomElement('.$names->enumClass($field->name).'::cases())';
    }

    public function formVariables(FieldDefinition $field): array
    {
        return ['PROPS' => '', 'OPTIONS' => $field->name];
    }

    public function display(FieldDefinition $field, string $variable): string
    {
        $value = "{$variable}.{$field->name}";

        return "{$value} ? <Badge>{optionLabel(options.{$field->name}, {$value})}</Badge> : '—'";
    }

    public function displayImports(FieldDefinition $field): array
    {
        return ['Badge', 'optionLabel'];
    }

    public function optionsExpression(FieldDefinition $field, ModuleNames $names): string
    {
        return $names->enumClass($field->name).'::options()';
    }
}
