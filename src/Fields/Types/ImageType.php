<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Fields\Types;

use Amon\ModuleGenerator\Definition\FieldDefinition;
use Amon\ModuleGenerator\Fields\FileLike;

final class ImageType extends FileLike
{
    protected string $name = 'image';

    protected string $label = 'Image';

    protected array $schema = [
        'disk' => ['type' => 'identifier', 'label' => 'Disque', 'default' => 'public'],
        'max_size' => ['type' => 'int', 'label' => 'Taille maximale (Ko)', 'default' => 2048, 'min' => 1, 'max' => 1048576],
        'extensions' => ['type' => 'list', 'label' => 'Extensions acceptées', 'default' => ['jpg', 'jpeg', 'png', 'webp']],
    ];

    protected function ruleBase(): string
    {
        return 'File::image()';
    }

    protected function placeholderExtension(): string
    {
        return 'png';
    }

    public function display(FieldDefinition $field, string $variable): string
    {
        $url = "{$variable}.{$field->name}_url";

        return "{$url} ? <img src={{$url}} alt=\"\" className=\"h-10 w-10 rounded object-cover\" /> : '—'";
    }
}
