<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Fields\Types;

use Amon\ModuleGenerator\Fields\AbstractFieldType;

final class PasswordType extends AbstractFieldType
{
    protected string $name = 'password';

    protected string $label = 'Mot de passe';

    protected array $allows = [];

    protected array $presentation = ['in_table' => false, 'in_detail' => false];

    protected array $schema = ['min' => ['type' => 'int', 'label' => 'Longueur minimale', 'default' => 8, 'min' => 1, 'max' => 255]];

    protected ?string $defaultKind = null;

    protected bool $pivotAllowed = false;
}
