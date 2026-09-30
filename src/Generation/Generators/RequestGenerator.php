<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Generation\Generators;

use Amon\ModuleGenerator\Contracts\Generator;
use Amon\ModuleGenerator\Definition\PivotSide;
use Amon\ModuleGenerator\Definition\RelationType;
use Amon\ModuleGenerator\Generation\Code;
use Amon\ModuleGenerator\Generation\Facts;
use Amon\ModuleGenerator\Generation\GenerationContext;
use Amon\ModuleGenerator\Generation\PlannedFile;
use Amon\ModuleGenerator\Support\Escaper;

/** Requests de création et de mise à jour : règles par type, unicité, relations, polymorphe, arbre, couple pivot. */
final class RequestGenerator implements Generator
{
    /** @var list<string> */
    private array $imports = [];

    /** @var array<string, list<string>> */
    private array $rules = [];

    /** @var array<string, string> */
    private array $attributes = [];

    public function applies(GenerationContext $context): bool
    {
        return true;
    }

    public function generate(GenerationContext $context): array
    {
        $files = [$this->request($context, false)];

        if (! Facts::isPair($context->definition)) {
            $files[] = $this->request($context, true);
        }

        return $files;
    }

    private function request(GenerationContext $context, bool $update): PlannedFile
    {
        $names = $context->names;
        $definition = $context->definition;
        $class = $update ? $names->updateRequest : $names->storeRequest;
        $this->imports = ['Illuminate\Foundation\Http\FormRequest'];
        $this->rules = [];
        $this->attributes = [];
        $prepare = '';

        if ($definition->pivot !== null) {
            foreach ([$definition->pivot->left, $definition->pivot->right] as $index => $side) {
                if (! $side->polymorphic) {
                    $this->add((string) $side->foreignKey, $this->sideRules($context, $side, $index === 1, $update), (string) $side->model);
                }
            }
        }

        if (Facts::morphInForm($definition)) {
            $this->morphRules($context);
        }

        if ($definition->tree) {
            $this->import('Illuminate\Validation\Rule');
            $parentRules = ["'nullable'", "'integer'", 'Rule::exists('.Escaper::php($names->table).", 'id')"];

            if ($update) {
                $prepare = "        \${$names->variable} = \$this->route(".Escaper::php($names->routeParameter).");\n\n";
                $parentRules[] = "Rule::notIn([\${$names->variable}->getKey(), ...\${$names->variable}->descendants()->pluck('id')])";
            }

            $this->add('parent_id', $parentRules, 'Parent');
        }

        foreach (Facts::formFields($definition) as $field) {
            $type = $context->type($field);
            $this->add($field->name, $type->rules($field, $names, $update), $field->label);
            array_push($this->imports, ...$type->imports($field, $names));
        }

        foreach ($definition->relations as $relation) {
            if (! $relation->inForm) {
                continue;
            }

            $this->import('Illuminate\Validation\Rule');
            $exists = 'Rule::exists('.Escaper::php((string) $relation->targetTable).", 'id')";

            if ($relation->type === RelationType::BelongsTo) {
                $presence = Escaper::php($relation->nullable ? 'nullable' : 'required');
                $this->add((string) $relation->foreignKey, [$presence, "'integer'", $exists], $relation->label);
            } else {
                $this->add($relation->name, ["'array'"], $relation->label);
                $this->rules[$relation->name.'.*'] = ["'integer'", $exists];
            }
        }

        $content = $context->render('request', [
            'IMPORTS' => Code::uses($this->imports, 'App\Http\Requests\Admin'),
            'CLASS' => $class,
            'PREPARE' => $prepare,
            'RULES' => Code::arrayLines(array_map(fn (array $list) => '['.implode(', ', $list).']', $this->rules), 3),
            'ATTRIBUTES' => Code::arrayLines(array_map(Escaper::php(...), $this->attributes), 3),
        ]);
        $label = ($update ? 'Request de mise à jour ' : 'Request de création ').$class;

        return new PlannedFile('app/Http/Requests/Admin/'.$class.'.php', $content, 'request', $label);
    }

    /** @param  list<string>  $rules */
    private function add(string $key, array $rules, string $label): void
    {
        $this->rules[$key] = $rules;
        $this->attributes[$key] = $label;
    }

    private function import(string $class): string
    {
        $this->imports[] = $class;

        return Code::basename($class);
    }

    private function morphRules(GenerationContext $context): void
    {
        $morph = $context->definition->morph;
        $constant = $this->import($context->names->modelClass).'::'.Facts::morphConstant($morph->name);
        $this->import('Closure');
        $this->import('Illuminate\Validation\Rule');
        $typeKey = $morph->name.'_type';
        $idKey = $morph->name.'_id';

        $typePresence = $morph->nullable ? ["'nullable'", Escaper::php('required_with:'.$idKey)] : ["'required'"];
        $idPresence = $morph->nullable ? ["'nullable'", Escaper::php('required_with:'.$typeKey)] : ["'required'"];

        $exists = "function (string \$attribute, mixed \$value, Closure \$fail): void {\n"
            .'    $type = $this->input('.Escaper::php($typeKey).");\n\n"
            ."    if (! is_string(\$type) || ! array_key_exists(\$type, {$constant}) || ! \$type::query()->whereKey(\$value)->exists()) {\n"
            ."        \$fail('validation.exists')->translate();\n"
            ."    }\n}";

        $this->add($typeKey, [...$typePresence, "Rule::in(array_keys({$constant}))"], 'Type');
        $this->add($idKey, [...$idPresence, $exists], 'Élément');
    }

    /** @return list<string> */
    private function sideRules(GenerationContext $context, PivotSide $side, bool $second, bool $update): array
    {
        $this->import('Illuminate\Validation\Rule');
        $rules = ["'required'", "'integer'", 'Rule::exists('.Escaper::php((string) $side->table).", 'id')"];
        $pivot = $context->definition->pivot;

        // L'unicité du couple est portée par la seconde clé.
        if (! $second || ! $pivot->uniquePair) {
            return $rules;
        }

        $other = $pivot->left;
        $rule = 'Rule::unique('.Escaper::php($context->names->table).', '.Escaper::php((string) $side->foreignKey).')';

        if ($other->polymorphic) {
            foreach (['_type', '_id'] as $suffix) {
                $column = Escaper::php($context->definition->morph?->name.$suffix);
                $rule .= "\n    ->where({$column}, \$this->input({$column}))";
            }
        } else {
            $column = Escaper::php((string) $other->foreignKey);
            $rule .= "\n    ->where({$column}, \$this->input({$column}))";
        }

        if ($update) {
            $rule .= "\n    ->ignore(\$this->route(".Escaper::php($context->names->routeParameter).'))';
        }

        $rules[] = $rule;

        return $rules;
    }
}
