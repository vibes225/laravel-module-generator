<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Fields;

use Amon\ModuleGenerator\Definition\FieldDefinition;
use Amon\ModuleGenerator\Generation\ModuleNames;
use Amon\ModuleGenerator\Support\Escaper;

/**
 * Fichiers : chemin stocké en chaîne, URL exposée par un accesseur `<champ>_url` du modèle.
 * Le contrôleur stocke le fichier et supprime l'ancien au remplacement.
 */
abstract class FileLike extends AbstractFieldType
{
    protected bool $pivotAllowed = false;

    protected string $fragment = 'file';

    abstract protected function ruleBase(): string;

    abstract protected function placeholderExtension(): string;

    protected function typeRules(FieldDefinition $field, ModuleNames $names): array
    {
        $types = implode(', ', array_map(Escaper::php(...), $field->options['extensions']));

        return [$this->ruleBase().'->types(['.$types.'])->max('.(int) $field->options['max_size'].')'];
    }

    public function imports(FieldDefinition $field, ModuleNames $names): array
    {
        return ['Illuminate\Validation\Rules\File'];
    }

    protected function factoryExpression(FieldDefinition $field, ModuleNames $names): string
    {
        return $field->nullable ? 'null' : Escaper::php($names->slug.'/placeholder.'.$this->placeholderExtension());
    }

    public function formVariables(FieldDefinition $field): array
    {
        $accept = implode(',', array_map(fn (string $extension) => '.'.$extension, $field->options['extensions']));

        return ['PROPS' => ' accept="'.$accept.'"'];
    }

    public function formInitial(FieldDefinition $field): string
    {
        return 'null';
    }

    public function isFile(): bool
    {
        return true;
    }

    public function disk(FieldDefinition $field): string
    {
        return (string) $field->options['disk'];
    }
}
