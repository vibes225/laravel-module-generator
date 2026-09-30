<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Fields\Types;

use Amon\ModuleGenerator\Fields\AbstractFieldType;

final class ForeignIdType extends AbstractFieldType
{
    protected string $name = 'foreignId';

    protected string $label = 'Clé étrangère';

    protected array $allows = ['unique', 'sortable', 'filterable'];

    protected array $presentation = ['filterable' => true];

    protected array $schema = ['references' => ['type' => 'identifier', 'label' => 'Table référencée', 'required' => true], 'on_delete' => ['type' => 'select', 'label' => 'À la suppression', 'default' => 'restrict', 'choices' => ['cascade', 'restrict', 'null', 'no_action']]];

    protected ?string $defaultKind = null;

    protected bool $pivotAllowed = true;
}
