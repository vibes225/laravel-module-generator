<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Fields\Types;

use Amon\ModuleGenerator\Fields\AbstractFieldType;

final class JsonType extends AbstractFieldType
{
    protected string $name = 'json';

    protected string $label = 'JSON';

    protected array $allows = [];

    protected array $presentation = ['in_table' => false];

    protected array $schema = [];

    protected ?string $defaultKind = null;

    protected bool $pivotAllowed = true;
}
