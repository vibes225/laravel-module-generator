<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Generation\Generators;

use Amon\ModuleGenerator\Definition\RelationType;
use Amon\ModuleGenerator\Generation\Code;
use Amon\ModuleGenerator\Generation\Facts;
use Amon\ModuleGenerator\Generation\GenerationContext;
use Amon\ModuleGenerator\Support\Escaper;
use Illuminate\Support\Str;

/** Formulaire partagé (création et modification) assemblé à partir des fragments par type. */
final class FormBuilder
{
    private const COMPONENTS = [
        'input' => 'Input',
        'password' => 'Input',
        'file' => 'Input',
        'textarea' => 'Textarea',
        'json' => 'Textarea',
        'select' => 'Select',
        'morph' => 'Select',
        'date' => 'DatePicker',
        'switch' => 'Switch',
        'multiselect' => 'MultiSelect',
    ];

    /** @var list<string> */
    private array $initial = [];

    /** @var list<string> */
    private array $fields = [];

    /** @var list<string> */
    private array $imports = ['Button'];

    public function build(GenerationContext $context, string $kitPath): string
    {
        $definition = $context->definition;
        $pivot = $definition->pivot;

        foreach ($pivot === null ? [] : [$pivot->left, $pivot->right] as $side) {
            if (! $side->polymorphic) {
                $this->select($context, (string) $side->foreignKey, (string) $side->model, true);
            }
        }

        if (Facts::morphInForm($definition)) {
            $morph = $definition->morph;
            $this->initial[] = "{$morph->name}_type: record?.{$morph->name}_type ?? '',";
            $this->initial[] = "{$morph->name}_id: record?.{$morph->name}_id ?? '',";
            $this->fragment($context, 'morph', ['NAME' => $morph->name, 'REQUIRED' => $morph->nullable ? '' : ' required']);
        }

        if ($definition->tree) {
            $this->select($context, 'parent_id', 'Parent', false);
        }

        foreach (Facts::formFields($definition) as $field) {
            $type = $context->type($field);
            $fragment = $type->formFragment($field);
            $required = '';

            if (! $field->nullable && $fragment !== 'switch') {
                $required = in_array($fragment, ['password', 'file'], true) ? ' required={!record}' : ' required';
            }

            $this->initial[] = "{$field->name}: ".$type->formInitial($field).',';
            $this->fragment($context, $fragment, [
                'NAME' => $field->name,
                'LABEL' => Escaper::js($field->label),
                'REQUIRED' => $required,
                'OPTIONS' => $field->name,
                ...$type->formVariables($field),
            ]);
        }

        foreach ($definition->relations as $relation) {
            if (! $relation->inForm) {
                continue;
            }

            if ($relation->type === RelationType::BelongsTo) {
                $this->select($context, (string) $relation->foreignKey, $relation->label, ! $relation->nullable);
            } else {
                $this->initial[] = "{$relation->name}: record?.{$relation->name} ?? [],";
                $this->fragment($context, 'multiselect', ['NAME' => $relation->name, 'LABEL' => Escaper::js($relation->label), 'OPTIONS' => $relation->name]);
            }
        }

        $names = $context->names;
        $route = $names->routeName;
        $hasFiles = Facts::fileFields($definition, $context->types) !== [];

        if (Facts::isPair($definition)) {
            $methods = 'post';
            $submit = "post(route('{$route}.store'));";
        } elseif ($hasFiles) {
            $methods = 'post, transform';
            $submit = "if (record) {\n    transform((values) => ({ ...values, _method: 'put' }));\n"
                ."    post(route('{$route}.update', record.id), { forceFormData: true });\n} else {\n"
                ."    post(route('{$route}.store'), { forceFormData: true });\n}";
        } else {
            $methods = 'post, put';
            $submit = "if (record) {\n    put(route('{$route}.update', record.id));\n} else {\n    post(route('{$route}.store'));\n}";
        }

        return $context->render('pages/form', [
            'KIT' => implode(', ', PageGenerator::sortImports($this->imports)),
            'KIT_PATH' => $kitPath,
            'FORM' => Str::studly($names->formComponent),
            'FORM_METHODS' => $methods,
            'INITIAL' => Code::indent(implode("\n", $this->initial), 2),
            'SUBMIT' => Code::indent($submit, 2),
            'FIELDS' => Code::indent(implode("\n", $this->fields), 3),
            'ROUTE' => $route,
        ]);
    }

    private function select(GenerationContext $context, string $name, string $label, bool $required): void
    {
        $this->initial[] = "{$name}: record?.{$name} ?? '',";
        $this->fragment($context, 'select', [
            'NAME' => $name,
            'LABEL' => Escaper::js($label),
            'REQUIRED' => $required ? ' required' : '',
            'OPTIONS' => $name,
        ]);
    }

    /** @param  array<string, string>  $values */
    private function fragment(GenerationContext $context, string $fragment, array $values): void
    {
        $this->imports[] = self::COMPONENTS[$fragment] ?? 'Input';
        $this->fields[] = rtrim($context->render('fragments/form/'.$fragment, $values + ['PROPS' => '', 'REQUIRED' => '']));
    }
}
