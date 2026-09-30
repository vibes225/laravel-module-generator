<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Fields\Types;

use Amon\ModuleGenerator\Fields\AbstractFieldType;

final class FileType extends AbstractFieldType
{
    protected string $name = 'file';

    protected string $label = 'Fichier';

    protected array $allows = [];

    protected array $presentation = ['in_table' => false];

    protected array $schema = ['disk' => ['type' => 'identifier', 'label' => 'Disque', 'default' => 'public'], 'max_size' => ['type' => 'int', 'label' => 'Taille maximale (Ko)', 'default' => 2048, 'min' => 1, 'max' => 1048576], 'extensions' => ['type' => 'list', 'label' => 'Extensions acceptées', 'default' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip']]];

    protected ?string $defaultKind = null;

    protected bool $pivotAllowed = false;
}
