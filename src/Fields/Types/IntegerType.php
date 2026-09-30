<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Fields\Types;

use Amon\ModuleGenerator\Fields\IntegerLike;

final class IntegerType extends IntegerLike
{
    protected string $name = 'integer';

    protected string $label = 'Entier';
}
