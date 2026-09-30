<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Generation\Generators;

use Amon\ModuleGenerator\Contracts\Generator;
use Amon\ModuleGenerator\Definition\PivotMode;
use Amon\ModuleGenerator\Definition\RelationDefinition;
use Amon\ModuleGenerator\Definition\RelationType;
use Amon\ModuleGenerator\Fields\FileLike;
use Amon\ModuleGenerator\Generation\Code;
use Amon\ModuleGenerator\Generation\Facts;
use Amon\ModuleGenerator\Generation\GenerationContext;
use Amon\ModuleGenerator\Generation\PlannedFile;
use Amon\ModuleGenerator\Support\Escaper;
use Illuminate\Support\Str;

final class ModelGenerator implements Generator
{
    private const RELATION_CLASSES = [
        'belongsTo' => 'Illuminate\Database\Eloquent\Relations\BelongsTo',
        'hasOne' => 'Illuminate\Database\Eloquent\Relations\HasOne',
        'hasMany' => 'Illuminate\Database\Eloquent\Relations\HasMany',
        'belongsToMany' => 'Illuminate\Database\Eloquent\Relations\BelongsToMany',
    ];

    /** @var list<string> */
    private array $imports = [];

    public function applies(GenerationContext $context): bool
    {
        return true;
    }

    public function generate(GenerationContext $context): array
    {
        $definition = $context->definition;
        $names = $context->names;
        $this->imports = ['Illuminate\Database\Eloquent\Factories\HasFactory'];
        $traits = ['HasFactory'];

        if ($definition->pivot !== null) {
            $morphSide = $definition->pivot->left->polymorphic || $definition->pivot->right->polymorphic;
            $parent = $this->import($morphSide ? 'Illuminate\Database\Eloquent\Relations\MorphPivot' : 'Illuminate\Database\Eloquent\Relations\Pivot');
        } else {
            $parent = $this->import('Illuminate\Database\Eloquent\Model');
        }

        if ($definition->options->softDeletes) {
            $traits[] = $this->import('Illuminate\Database\Eloquent\SoftDeletes');
        }

        if ($definition->tree) {
            $traits[] = $this->import('Kalnoy\Nestedset\NodeTrait');
        }

        $blocks = ['use '.implode(', ', $traits).';'];

        if (Facts::morphInForm($definition)) {
            $blocks[] = $this->morphTypes($context);
        }

        $blocks[] = 'protected $table = '.Escaper::php($names->table).';';

        if ($definition->pivot !== null && $definition->pivot->primaryId) {
            $blocks[] = 'public $incrementing = true;';
        }

        $blocks[] = 'protected $fillable = '.Code::listBlock($this->fillable($context), 0).';';

        $hidden = array_map(fn ($field) => $field->name, Facts::fieldsOfType($definition, 'password', inFormOnly: false));

        if ($hidden !== []) {
            $blocks[] = 'protected $hidden = '.Code::listBlock($hidden, 0).';';
        }

        $files = Facts::fileFields($definition, $context->types);

        if ($files !== []) {
            $blocks[] = 'protected $appends = '.Code::listBlock(array_map(fn ($field) => $field->name.'_url', $files), 0).';';
        }

        if (($casts = $this->casts($context)) !== null) {
            $blocks[] = $casts;
        }

        foreach ($files as $field) {
            $blocks[] = $this->urlAccessor($field->name, $context->types->get($field->type) instanceof FileLike ? $context->types->get($field->type)->disk($field) : 'public');
        }

        array_push($blocks, ...$this->relationMethods($context));

        $content = $context->render('model', [
            'NAMESPACE' => $names->modelNamespace,
            'IMPORTS' => Code::uses($this->imports, $names->modelNamespace),
            'CLASS' => $names->model,
            'PARENT' => $parent,
            'BODY' => Code::indent(implode("\n\n", $blocks), 1),
        ]);

        return [new PlannedFile($names->modelPath(), $content, 'model', "Modèle {$names->model}")];
    }

    private function import(string $class): string
    {
        $this->imports[] = $class;

        return Code::basename($class);
    }

    /** @return list<string> */
    private function fillable(GenerationContext $context): array
    {
        $definition = $context->definition;
        $fillable = [];

        if ($definition->pivot !== null) {
            foreach ([$definition->pivot->left, $definition->pivot->right] as $side) {
                if (! $side->polymorphic) {
                    $fillable[] = (string) $side->foreignKey;
                }
            }
        }

        if ($definition->morph !== null) {
            array_push($fillable, $definition->morph->name.'_type', $definition->morph->name.'_id');
        }

        if ($definition->tree) {
            $fillable[] = 'parent_id';
        }

        foreach ($definition->fields as $field) {
            $fillable[] = $field->name;
        }

        foreach (Facts::relations($definition, RelationType::BelongsTo) as $relation) {
            $fillable[] = (string) $relation->foreignKey;
        }

        return $fillable;
    }

    private function casts(GenerationContext $context): ?string
    {
        $casts = [];

        foreach ($context->definition->fields as $field) {
            $type = $context->type($field);

            if (($cast = $type->cast($field, $context->names)) !== null) {
                $casts[$field->name] = $cast;

                foreach ($type->imports($field, $context->names) as $class) {
                    if (str_starts_with($class, 'App\Enums')) {
                        $this->imports[] = $class;
                    }
                }
            }
        }

        if ($casts === []) {
            return null;
        }

        return "/**\n * @return array<string, string>\n */\nprotected function casts(): array\n{\n    return [\n"
            .Code::arrayLines($casts, 2)."\n    ];\n}";
    }

    private function urlAccessor(string $field, string $disk): string
    {
        $this->import('Illuminate\Database\Eloquent\Casts\Attribute');
        $this->import('Illuminate\Support\Facades\Storage');
        $method = Str::camel($field).'Url';

        return "protected function {$method}(): Attribute\n{\n    return Attribute::get(\n"
            ."        fn () => \$this->{$field} ? Storage::disk(".Escaper::php($disk).")->url(\$this->{$field}) : null,\n    );\n}";
    }

    /** Constante des types polymorphes autorisés : classe => libellé. */
    private function morphTypes(GenerationContext $context): string
    {
        $morph = $context->definition->morph;
        $lines = [];

        foreach ($morph->types as $type) {
            $lines[] = $this->import($type->class).'::class => '.Escaper::php($type->label).',';
        }

        return "/**\n * Types autorisés pour la relation {$morph->name} : classe => libellé.\n */\n"
            .'public const '.Facts::morphConstant($morph->name)." = [\n".Code::indent(implode("\n", $lines), 1)."\n];";
    }

    /** @return list<string> */
    private function relationMethods(GenerationContext $context): array
    {
        $definition = $context->definition;
        $methods = [];

        if ($definition->pivot !== null) {
            foreach ([$definition->pivot->left, $definition->pivot->right] as $side) {
                if ($side->polymorphic) {
                    continue;
                }

                $target = $this->import($context->names->modelFqcn((string) $side->model));
                $methods[] = $this->method(Facts::sideMethod($side), $this->import(self::RELATION_CLASSES['belongsTo']), "\$this->belongsTo({$target}::class, ".Escaper::php((string) $side->foreignKey).')');
            }
        }

        if ($definition->morph !== null) {
            $class = $this->import('Illuminate\Database\Eloquent\Relations\MorphTo');
            $methods[] = $this->method(Str::camel($definition->morph->name), $class, '$this->morphTo()');
        }

        foreach ($definition->relations as $relation) {
            $methods[] = $this->relation($context, $relation);
        }

        return $methods;
    }

    private function relation(GenerationContext $context, RelationDefinition $relation): string
    {
        $returnType = $this->import(self::RELATION_CLASSES[$relation->type->value]);
        $target = $this->import($context->names->modelFqcn($relation->target)).'::class';
        $key = Escaper::php((string) $relation->foreignKey);

        $body = match ($relation->type) {
            RelationType::BelongsTo => "\$this->belongsTo({$target}, {$key})",
            RelationType::HasOne => "\$this->hasOne({$target}, {$key})",
            RelationType::HasMany => "\$this->hasMany({$target}, {$key})",
            RelationType::BelongsToMany => $this->belongsToMany($context, $relation, $target),
        };

        return $this->method($relation->name, $returnType, $body);
    }

    private function belongsToMany(GenerationContext $context, RelationDefinition $relation, string $target): string
    {
        $pivot = $relation->pivot;

        if ($pivot === null || $pivot->mode === PivotMode::None) {
            return "\$this->belongsToMany({$target})";
        }

        $call = "\$this->belongsToMany({$target}, ".Code::stringList([(string) $pivot->table, (string) $relation->foreignPivotKey, (string) $relation->relatedPivotKey]).')';

        if ($pivot->mode === PivotMode::Module) {
            $call .= "\n    ->using(".$this->import($context->names->modelFqcn((string) $pivot->model)).'::class)';
        }

        $extra = array_map(fn ($field) => $field->name, $pivot->fields);

        if ($extra !== []) {
            $call .= "\n    ->withPivot(".Code::stringList($extra).')';
        }

        if ($pivot->timestamps) {
            $call .= "\n    ->withTimestamps()";
        }

        return $call;
    }

    private function method(string $name, string $returnType, string $body): string
    {
        return "public function {$name}(): {$returnType}\n{\n    return ".str_replace("\n", "\n    ", $body).";\n}";
    }
}
