<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Fields\Types;

use Amon\ModuleGenerator\Definition\FieldDefinition;
use Amon\ModuleGenerator\Fields\AbstractFieldType;
use Amon\ModuleGenerator\Generation\ModuleNames;
use Amon\ModuleGenerator\Support\Escaper;

final class PasswordType extends AbstractFieldType
{
    protected string $name = 'password';

    protected string $label = 'Mot de passe';

    protected array $presentation = ['in_table' => false, 'in_detail' => false];

    protected array $schema = [
        'min' => ['type' => 'int', 'label' => 'Longueur minimale', 'default' => 8, 'min' => 1, 'max' => 255],
    ];

    protected bool $pivotAllowed = false;

    protected ?string $castAs = 'hashed';

    protected string $fragment = 'password';

    protected string $factory = "'password'";

    protected function typeRules(FieldDefinition $field, ModuleNames $names): array
    {
        return ["'string'", Escaper::php('min:'.($field->options['min'] ?? 8))];
    }

    public function formInitial(FieldDefinition $field): string
    {
        return "''";
    }

    public function display(FieldDefinition $field, string $variable): string
    {
        return "'••••••••'";
    }
}
