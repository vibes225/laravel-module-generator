<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Fields;

use Amon\ModuleGenerator\Contracts\FieldType;
use Amon\ModuleGenerator\Definition\FieldDefinition;
use Amon\ModuleGenerator\Generation\ModuleNames;
use Amon\ModuleGenerator\Support\Escaper;
use DateTimeImmutable;

/**
 * Base des types : chaque type se décrit par des propriétés ; la validation des options suit le schéma.
 */
abstract class AbstractFieldType implements FieldType
{
    private const BASE_PRESENTATION = [
        'searchable' => false,
        'sortable' => false,
        'filterable' => false,
        'in_table' => true,
        'in_form' => true,
        'in_detail' => true,
    ];

    protected string $name;

    protected string $label;

    /** @var list<string> */
    protected array $allows = [];

    /** @var array<string, bool> */
    protected array $presentation = [];

    /** @var array<string, array<string, mixed>> */
    protected array $schema = [];

    /** string|int|number|bool|date|datetime|time|enum ; null = pas de valeur par défaut. */
    protected ?string $defaultKind = null;

    protected bool $pivotAllowed = true;

    public function name(): string
    {
        return $this->name;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function optionSchema(): array
    {
        return $this->schema;
    }

    public function allows(): array
    {
        return $this->allows;
    }

    public function presentationDefaults(): array
    {
        return array_merge(self::BASE_PRESENTATION, $this->presentation);
    }

    public function allowedInPivot(): bool
    {
        return $this->pivotAllowed;
    }

    public function normalizeOptions(array $options): array
    {
        foreach ($this->schema as $key => $definition) {
            if (($options[$key] ?? null) === null && array_key_exists('default', $definition)) {
                $options[$key] = $definition['default'];
            }
        }

        return $options;
    }

    public function validateOptions(array $options): array
    {
        $errors = [];

        foreach (array_keys($options) as $key) {
            if (! isset($this->schema[$key])) {
                $errors[(string) $key] = "Option inconnue pour le type {$this->name}.";
            }
        }

        foreach ($this->schema as $key => $definition) {
            $value = $options[$key] ?? null;

            if ($value === null) {
                if ($definition['required'] ?? false) {
                    $errors[$key] = 'Option requise.';
                }

                continue;
            }

            if ($error = $this->validateOption($value, $definition)) {
                $errors[$key] = $error;
            }
        }

        return $errors;
    }

    /** @param  array<string, mixed>  $definition */
    protected function validateOption(mixed $value, array $definition): ?string
    {
        switch ($definition['type']) {
            case 'int':
                if (! is_int($value)) {
                    return 'Un entier est attendu.';
                }

                if (isset($definition['min']) && $value < $definition['min']) {
                    return 'La valeur minimale est '.$definition['min'].'.';
                }

                return isset($definition['max']) && $value > $definition['max'] ? 'La valeur maximale est '.$definition['max'].'.' : null;
            case 'bool':
                return is_bool($value) ? null : 'Un booléen est attendu.';
            case 'select':
                return in_array($value, $definition['choices'], true) ? null : 'Valeur attendue : '.implode(', ', $definition['choices']).'.';
            case 'identifier':
                return is_string($value) && preg_match('/^[a-z][a-z0-9_]*$/', $value) ? null : 'Identifiant invalide (minuscules, chiffres, _).';
            case 'list':
                return $this->validateStringList($value);
        }

        return null;
    }

    protected function validateStringList(mixed $value): ?string
    {
        if (! is_array($value) || ! array_is_list($value) || $value === []) {
            return 'Une liste non vide est attendue.';
        }

        foreach ($value as $item) {
            if (! is_string($item) || ! preg_match('/^[a-z0-9]{1,10}$/', $item)) {
                return 'Extensions attendues en minuscules, sans point (ex. pdf, png).';
            }
        }

        return count(array_unique($value)) === count($value) ? null : 'La liste contient des doublons.';
    }

    public function validateDefault(mixed $value, array $options): ?string
    {
        return match ($this->defaultKind) {
            'string' => is_string($value) ? null : 'Une chaîne est attendue.',
            'int' => is_int($value) ? null : 'Un entier est attendu.',
            'number' => is_int($value) || is_float($value) ? null : 'Un nombre est attendu.',
            'bool' => is_bool($value) ? null : 'Un booléen est attendu.',
            'date' => $this->validateDateTime($value, ['Y-m-d'], 'AAAA-MM-JJ'),
            'datetime' => $this->validateDateTime($value, ['Y-m-d H:i:s', 'Y-m-d H:i'], 'AAAA-MM-JJ HH:MM:SS'),
            'time' => $this->validateDateTime($value, ['H:i:s', 'H:i'], 'HH:MM'),
            'enum' => $this->validateEnumDefault($value, $options),
            default => 'Ce type ne supporte pas de valeur par défaut.',
        };
    }

    /** @param  list<string>  $formats */
    private function validateDateTime(mixed $value, array $formats, string $expected): ?string
    {
        if (is_string($value)) {
            foreach ($formats as $format) {
                $date = DateTimeImmutable::createFromFormat('!'.$format, $value);

                if ($date && $date->format($format) === $value) {
                    return null;
                }
            }
        }

        return 'Format attendu : '.$expected.'.';
    }

    /** @param  array<string, mixed>  $options */
    protected function validateEnumDefault(mixed $value, array $options): ?string
    {
        return null;
    }

    // --- Génération ---

    /** Méthode Blueprint de la colonne. */
    protected string $column = 'string';

    /** Cast Eloquent (sans guillemets), ou null. */
    protected ?string $castAs = null;

    /** @var list<string> règles propres au type, en expressions PHP */
    protected array $typeRules = [];

    protected string $fragment = 'input';

    /** @var array<string, string> props JSX supplémentaires du champ (valeurs littérales) */
    protected array $inputProps = [];

    protected string $factory = 'fake()->word()';

    /** Fonction du kit appliquée à l'affichage (`formatDate`), ou null pour la valeur brute. */
    protected ?string $displayHelper = null;

    public function migrationColumn(FieldDefinition $field): string
    {
        $column = '$table->'.$this->columnMethod($field).'('.implode(', ', $this->columnArguments($field)).')';

        if ($field->nullable) {
            $column .= '->nullable()';
        }

        if ($field->default !== null) {
            $column .= '->default('.Escaper::phpValue($field->default).')';
        }

        if ($field->unique) {
            $column .= '->unique()';
        } elseif ($field->index) {
            $column .= '->index()';
        }

        return $column.$this->columnSuffix($field);
    }

    protected function columnMethod(FieldDefinition $field): string
    {
        return $this->column;
    }

    /** @return list<string> */
    protected function columnArguments(FieldDefinition $field): array
    {
        return [Escaper::php($field->name)];
    }

    protected function columnSuffix(FieldDefinition $field): string
    {
        return '';
    }

    public function cast(FieldDefinition $field, ModuleNames $names): ?string
    {
        return $this->castAs === null ? null : Escaper::php($this->castAs);
    }

    public function rules(FieldDefinition $field, ModuleNames $names, bool $update): array
    {
        $optionalOnUpdate = $update && ($this->isFile() || $this->name === 'password');
        $rules = [Escaper::php($field->nullable || $optionalOnUpdate ? 'nullable' : 'required')];
        array_push($rules, ...$this->typeRules($field, $names));

        if ($field->unique) {
            $rule = 'Rule::unique('.Escaper::php($names->table).', '.Escaper::php($field->name).')';
            $rules[] = $update ? $rule.'->ignore($this->route('.Escaper::php($names->routeParameter).'))' : $rule;
        }

        return $rules;
    }

    /** @return list<string> */
    protected function typeRules(FieldDefinition $field, ModuleNames $names): array
    {
        return $this->typeRules;
    }

    public function imports(FieldDefinition $field, ModuleNames $names): array
    {
        return $field->unique ? ['Illuminate\Validation\Rule'] : [];
    }

    public function factoryValue(FieldDefinition $field, ModuleNames $names): string
    {
        $value = $this->factoryExpression($field, $names);

        return $field->unique && str_starts_with($value, 'fake()->') ? 'fake()->unique()->'.substr($value, 8) : $value;
    }

    protected function factoryExpression(FieldDefinition $field, ModuleNames $names): string
    {
        return $this->factory;
    }

    public function formFragment(FieldDefinition $field): string
    {
        return $this->fragment;
    }

    public function formVariables(FieldDefinition $field): array
    {
        $props = '';

        foreach ($this->inputProps($field) as $prop => $value) {
            $props .= ' '.$prop.'="'.$value.'"';
        }

        return ['PROPS' => $props];
    }

    /** @return array<string, string> */
    protected function inputProps(FieldDefinition $field): array
    {
        return $this->inputProps;
    }

    public function formInitial(FieldDefinition $field): string
    {
        $default = match (true) {
            is_string($field->default) => Escaper::js($field->default),
            is_bool($field->default) => $field->default ? 'true' : 'false',
            is_int($field->default), is_float($field->default) => (string) $field->default,
            default => "''",
        };

        return "record?.{$field->name} ?? {$default}";
    }

    public function display(FieldDefinition $field, string $variable): string
    {
        $value = "{$variable}.{$field->name}";

        return $this->displayHelper === null ? "{$value} ?? '—'" : "{$this->displayHelper}({$value})";
    }

    public function displayImports(FieldDefinition $field): array
    {
        return $this->displayHelper === null ? [] : [$this->displayHelper];
    }

    public function optionsExpression(FieldDefinition $field, ModuleNames $names): ?string
    {
        return null;
    }

    public function isFile(): bool
    {
        return false;
    }
}
