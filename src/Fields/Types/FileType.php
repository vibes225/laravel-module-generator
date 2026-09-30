<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Fields\Types;

use Amon\ModuleGenerator\Definition\FieldDefinition;
use Amon\ModuleGenerator\Fields\FileLike;

final class FileType extends FileLike
{
    protected string $name = 'file';

    protected string $label = 'Fichier';

    protected array $presentation = ['in_table' => false];

    protected array $schema = [
        'disk' => ['type' => 'identifier', 'label' => 'Disque', 'default' => 'public'],
        'max_size' => ['type' => 'int', 'label' => 'Taille maximale (Ko)', 'default' => 2048, 'min' => 1, 'max' => 1048576],
        'extensions' => ['type' => 'list', 'label' => 'Extensions acceptées', 'default' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip']],
    ];

    protected function ruleBase(): string
    {
        return 'File::default()';
    }

    protected function placeholderExtension(): string
    {
        return 'pdf';
    }

    public function display(FieldDefinition $field, string $variable): string
    {
        $url = "{$variable}.{$field->name}_url";

        return "{$url} ? <a href={{$url}} target=\"_blank\" rel=\"noreferrer\" className=\"text-indigo-600 hover:underline\">Télécharger</a> : '—'";
    }
}
