<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Fields\Types;

use Amon\ModuleGenerator\Fields\AbstractFieldType;

final class ImageType extends AbstractFieldType
{
    protected string $name = 'image';

    protected string $label = 'Image';

    protected array $allows = [];

    protected array $presentation = [];

    protected array $schema = ['disk' => ['type' => 'identifier', 'label' => 'Disque', 'default' => 'public'], 'max_size' => ['type' => 'int', 'label' => 'Taille maximale (Ko)', 'default' => 2048, 'min' => 1, 'max' => 1048576], 'extensions' => ['type' => 'list', 'label' => 'Extensions acceptées', 'default' => ['jpg', 'jpeg', 'png', 'webp']]];

    protected ?string $defaultKind = null;

    protected bool $pivotAllowed = false;
}
