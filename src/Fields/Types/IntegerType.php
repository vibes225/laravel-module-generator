<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Fields\Types;

use Amon\ModuleGenerator\Fields\AbstractFieldType;

final class IntegerType extends AbstractFieldType
{
    protected string $name = 'integer';

    protected string $label = 'Entier';

    protected array $allows = ['unique', 'index', 'default', 'sortable', 'filterable'];

    protected array $presentation = ['sortable' => true];

    protected array $schema = ['unsigned' => ['type' => 'bool', 'label' => 'Non signé', 'default' => false], 'min' => ['type' => 'int', 'label' => 'Valeur minimale'], 'max' => ['type' => 'int', 'label' => 'Valeur maximale']];

    protected ?string $defaultKind = 'int';

    protected bool $pivotAllowed = true;
}
