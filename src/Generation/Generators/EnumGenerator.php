<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Generation\Generators;

use Amon\ModuleGenerator\Contracts\Generator;
use Amon\ModuleGenerator\Generation\Code;
use Amon\ModuleGenerator\Generation\Facts;
use Amon\ModuleGenerator\Generation\GenerationContext;
use Amon\ModuleGenerator\Generation\PlannedFile;
use Amon\ModuleGenerator\Support\Escaper;
use Illuminate\Support\Str;

/** Une classe d'enum PHP (avec libellés) par champ `enum`, appartenant au module. */
final class EnumGenerator implements Generator
{
    public function applies(GenerationContext $context): bool
    {
        return Facts::fieldsOfType($context->definition, 'enum', inFormOnly: false) !== [];
    }

    public function generate(GenerationContext $context): array
    {
        $files = [];

        foreach (Facts::fieldsOfType($context->definition, 'enum', inFormOnly: false) as $field) {
            $class = $context->names->enumClass($field->name);
            $cases = [];
            $labels = [];

            foreach ($field->options['values'] as $value) {
                $case = Str::studly($value['value']);
                $cases[] = "case {$case} = ".Escaper::php($value['value']).';';
                $labels[] = "self::{$case} => ".Escaper::php($value['label']).',';
            }

            $files[] = new PlannedFile(
                'app/Enums/'.$class.'.php',
                $context->render('enum', [
                    'CLASS' => $class,
                    'CASES' => Code::indent(implode("\n", $cases), 1),
                    'LABELS' => Code::indent(implode("\n", $labels), 3),
                ]),
                'enum',
                "Enum {$class}",
            );
        }

        return $files;
    }
}
