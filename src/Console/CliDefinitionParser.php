<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Console;

use InvalidArgumentException;

/**
 * Options CLI => entrée de définition (normalisée ensuite par le moteur, comme l'UI).
 *
 *   --field=name:string                  --field=status:enum(draft,sent):default=draft
 *   --field=price:decimal(12,2):nullable --field=user_id:foreignId(users,cascade)
 *   modificateurs : nullable unique index searchable sortable filterable hidden nodetail noform default=… label=…
 *   --relation=belongsTo:Client[:nom][:nullable][:display=colonne]
 *   --relation=belongsToMany:Tag[:pivot=none|generate|module=<slug>][:form][:timestamps]
 */
final class CliDefinitionParser
{
    private const FLAGS = [
        'nullable' => ['nullable', true],
        'unique' => ['unique', true],
        'index' => ['index', true],
        'searchable' => ['searchable', true],
        'sortable' => ['sortable', true],
        'filterable' => ['filterable', true],
        'hidden' => ['in_table', false],
        'nodetail' => ['in_detail', false],
        'noform' => ['in_form', false],
    ];

    /** @return array<string, mixed> */
    public function field(string $spec): array
    {
        $parts = $this->split($spec);

        if (count($parts) < 2 || $parts[0] === '' || $parts[1] === '') {
            throw new InvalidArgumentException("Champ invalide « {$spec} » : nom:type attendu.");
        }

        [$type, $arguments] = $this->callable($parts[1]);
        $field = ['name' => $parts[0], 'type' => $type];
        $options = $this->typeOptions($type, $arguments, $spec);

        if ($options !== []) {
            $field['options'] = $options;
        }

        foreach (array_slice($parts, 2) as $modifier) {
            if (isset(self::FLAGS[$modifier])) {
                [$key, $value] = self::FLAGS[$modifier];
                $field[$key] = $value;
            } elseif (str_starts_with($modifier, 'default=')) {
                $field['default'] = $this->scalar(substr($modifier, 8), $type);
            } elseif (str_starts_with($modifier, 'label=')) {
                $field['label'] = substr($modifier, 6);
            } else {
                throw new InvalidArgumentException("Modificateur inconnu « {$modifier} » dans « {$spec} ».");
            }
        }

        return $field;
    }

    /** @return array<string, mixed> */
    public function relation(string $spec): array
    {
        $parts = $this->split($spec);

        if (count($parts) < 2) {
            throw new InvalidArgumentException("Relation invalide « {$spec} » : type:Cible attendu.");
        }

        $relation = ['type' => $parts[0], 'target' => $parts[1]];

        foreach (array_slice($parts, 2) as $index => $part) {
            match (true) {
                $part === 'nullable' => $relation['nullable'] = true,
                $part === 'form' => $relation['in_form'] = true,
                $part === 'timestamps' => $relation['pivot']['timestamps'] = true,
                str_starts_with($part, 'display=') => $relation['display'] = substr($part, 8),
                str_starts_with($part, 'pivot=module=') => $relation['pivot'] = ['mode' => 'module', 'module' => substr($part, 13)] + ($relation['pivot'] ?? []),
                str_starts_with($part, 'pivot=') => $relation['pivot'] = ['mode' => substr($part, 6)] + ($relation['pivot'] ?? []),
                $index === 0 && preg_match('/^[a-z][a-zA-Z0-9]*$/', $part) === 1 => $relation['name'] = $part,
                default => throw new InvalidArgumentException("Option de relation inconnue « {$part} » dans « {$spec} »."),
            };
        }

        return $relation;
    }

    /** @return list<string> */
    public function list(?string $value): array
    {
        return $value === null || trim($value) === '' ? [] : array_values(array_filter(array_map('trim', explode(',', $value)), fn (string $item) => $item !== ''));
    }

    /** Découpe sur `:` hors parenthèses. @return list<string> */
    private function split(string $spec): array
    {
        $parts = [''];
        $depth = 0;

        foreach (str_split(trim($spec)) as $char) {
            $depth += match ($char) {
                '(' => 1,
                ')' => -1,
                default => 0,
            };

            if ($char === ':' && $depth === 0) {
                $parts[] = '';
            } else {
                $parts[array_key_last($parts)] .= $char;
            }
        }

        return $parts;
    }

    /** `enum(a,b)` => ['enum', ['a', 'b']]. @return array{string, list<string>} */
    private function callable(string $value): array
    {
        if (preg_match('/^(\w+)\((.*)\)$/', $value, $match) === 1) {
            return [$match[1], $this->list($match[2])];
        }

        return [$value, []];
    }

    /**
     * @param  list<string>  $arguments
     * @return array<string, mixed>
     */
    private function typeOptions(string $type, array $arguments, string $spec): array
    {
        if ($arguments === []) {
            return [];
        }

        $integer = function (string $value) use ($spec): int {
            if (! preg_match('/^-?\d+$/', $value)) {
                throw new InvalidArgumentException("Entier attendu dans « {$spec} ».");
            }

            return (int) $value;
        };

        return match ($type) {
            'string', 'email' => ['max' => $integer($arguments[0])],
            'password' => ['min' => $integer($arguments[0])],
            'decimal' => ['precision' => $integer($arguments[0])] + (isset($arguments[1]) ? ['scale' => $integer($arguments[1])] : []),
            'integer', 'bigInteger' => array_filter(['min' => $integer($arguments[0]), 'max' => isset($arguments[1]) ? $integer($arguments[1]) : null], fn ($value) => $value !== null),
            'enum' => ['values' => $arguments],
            'foreignId' => ['references' => $arguments[0]] + (isset($arguments[1]) ? ['on_delete' => $arguments[1]] : []),
            'file', 'image' => ['extensions' => $arguments],
            default => throw new InvalidArgumentException("Le type {$type} n'accepte pas d'options entre parenthèses (« {$spec} »)."),
        };
    }

    /** Valeur par défaut typée selon le type du champ. */
    private function scalar(string $value, string $type): string|int|float|bool
    {
        return match (true) {
            $type === 'boolean' && in_array($value, ['true', '1'], true) => true,
            $type === 'boolean' && in_array($value, ['false', '0'], true) => false,
            in_array($type, ['integer', 'bigInteger'], true) && preg_match('/^-?\d+$/', $value) === 1 => (int) $value,
            $type === 'decimal' && is_numeric($value) => $value + 0,
            default => $value,
        };
    }
}
